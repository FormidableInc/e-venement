<?php
/**********************************************************************************
*
*	    This file is part of e-venement.
*
*    e-venement is free software; you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation; either version 2 of the License.
*
*    e-venement is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with e-venement; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
*    Copyright (c) 2006-2015 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2006-2015 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php
    if ( !($this->getUser()->getTransaction() instanceof Transaction) )
      return $this->redirect('@homepage');
    
    // if it is a pay-only process
    $tid = intval($request->getParameter('transaction_id')).'' === ''.$request->getParameter('transaction_id','')
      ? $request->getParameter('transaction_id')
      : false;
    $this->transaction = $tid ? Doctrine::getTable('Transaction')->find($tid) : $this->getUser()->getTransaction();
    if ( $this->transaction->contact_id != $this->getUser()->getTransaction()->contact_id )
      $this->transaction = $this->getUser()->getTransaction();
    
    // harden data
    if ( $this->transaction->id == $this->getUser()->getTransactionId() )
      $this->getContext()->getConfiguration()->hardenIntegrity();
    
    try { $this->form = new ContactPublicForm($this->getUser()->getContact()); }
    catch ( liEvenementException $e )
    { $this->form = new ContactPublicForm; }
    
    if (!( $this->getUser()->getTransaction()->contact_id && !$request->hasParameter('contact') ))
    {
      // add the contact to the DB
      if ( !$this->form->getObject()->isNew() )
        $this->form->removePassword();
      
      if ( !$request->getParameter('contact', false) && $this->getUser()->getTransaction()->contact_id )
      {
        // it's a hack to avoid infinite loops with the option "app_contact_modify_coordinates_first"
        $data = array();
        foreach ( $this->form->getValidatorSchema()->getFields() as $fieldname => $validator )
        if ( Doctrine::getTable('Contact')->hasColumn($fieldname) )
          $data[$fieldname] = $this->getUser()->getTransaction()->Contact->$fieldname;
      
        $ws = $this->form->getWidgetSchema();
        $vs = $this->form->getValidatorSchema();
        unset($ws['special_groups_list'], $vs['special_groups_list']);
        
        if ( sfConfig::get('app_contact_professional', false) )
        foreach ( array('pro_email' => 'contact_email', 'pro_phone_number' => 'contact_number') as $vname => $field )
          $data[$vname] = $this->form->getObject()->Professionals[0]->$field;
        
        $this->form->bind($data);
      }
      else
         $this->form->bind($request->getParameter('contact'));
      
      try
      {
        if ( !$this->form->isValid() || sfConfig::get('app_texts_terms_conditions') && !$request->hasParameter('terms_conditions') )
        {
          error_log('An error occurred registering a contact ('.$this->form->getErrorSchema().')');
          $this->setTemplate('register');
          return;
        }
      }
      catch ( liOnlineSaleException $e )
      {
        $this->getContext()->getConfiguration()->loadHelpers('I18N');
        $this->getUser()->setFlash('error',__($e->getMessage()));
        return $this->redirect('login/index');
      }
      
      // save the contact, with a non-confirmed attribute
      if ( !$this->getUser()->getTransaction()->contact_id )
        $this->form->getObject()->Transactions[] = $this->getUser()->getTransaction();
      $this->form->getObject()->culture = $this->getUser()->getCulture();
      $this->contact = $this->form->save();
    }
    // remember the contact's informations
    $this->getUser()->setAttribute('contact_form_values', $this->form->getValues());
    
    // passes controls: the current transaction does not embed more MemberCards than allowed
    if ( ($max = sfConfig::get('app_member_cards_max_per_transaction', false))
      && ($nb = $this->getUser()->getTransaction()->MemberCards->count()) > sfConfig::get('app_member_cards_max_per_transaction') )
    {
      $this->getContext()->getConfiguration()->loadHelpers('I18N');
      $this->getUser()->setFlash('error', $str = __('You have ordered %%nb%% passes/member cards, but %%max%% is allowed. Please remove some of them...'));
      error_log('Transaction #'.$this->getUser()->getTransactionId().': '.$str);
      $this->redirect('transaction/show?id='.$this->getUser()->getTransactionId());
    }
    
    // passes controls: checks if the current transaction does not have more than the maximum acceptable
    if ( $this->getUser()->getTransaction()->MemberCards->count() > 0 )
    {
      $mcs = array();
      foreach ( $this->getUser()->getTransaction()->MemberCards as $mc )
      foreach ( $mc->MemberCardPrices as $mcp )
      {
        if ( !isset($mcs[$mcp->price_id]) )
          $mcs[$mcp->price_id] = array('' => 0);
        if ( $mcp->event_id && !isset($mcs[$mcpm->price_id][$mcp->event_id]) )
          $mcs[$mcp->price_id][$mcp->event_id] = 0;
        $mcs[$mcp->price_id][$mcp->event_id ? $mcp->event_id : '']++;
      }
      
      foreach ( $this->getUser()->getTransaction()->Tickets as $ticket )
      if ( $ticket->Price->member_card_linked )
      {
        if ( isset($mcs[$ticket->price_id][$ticket->Manifestation->event_id]) && $mcs[$ticket->price_id][$ticket->Manifestation->event_id] > 0 )
          $mcs[$ticket->price_id][$ticket->Manifestation->event_id]--;
        else
          $mcs[$ticket->price_id]['']--;
      }
      
      $go = true;
      foreach ( $mcs as $price )
      if ( $price[''] < 0 )
      {
        $go = false;
        break;
      }
      
      if ( !$go )
      {
        $this->getContext()->getConfiguration()->loadHelpers('I18N');
        $this->getUser()->setFlash('error', $str = __('You have booked more tickets linked to a member card than you can...'));
        error_log('Transaction #'.$this->getUser()->getTransactionId().': '.$str);
        $this->redirect('transaction/show?id='.$this->getUser()->getTransactionId());
      }
    }
    
    // auto_passes: having at least $nb events linked to the current member cards
    $nb = array();
    foreach ( Doctrine::getTable('MemberCardType')->createQuery('mct')->execute() as $mct )
      $nb[$mct->name] = $mct->nb_tickets_mini;
    if ( $nb )
    {
      // the match maker
      $match = array();
      foreach ( $this->getUser()->getTransaction()->MemberCards as $mc )
      foreach ( $mc->MemberCardPrices as $mcp )
      {
        if ( !isset($match[$mcp->price_id]) )
          $match[$mcp->price_id] = array();
        if ( !isset($match[$mcp->price_id][$mcp->event_id ? $mcp->event_id : '']) )
          $match[$mcp->price_id][$mcp->event_id ? $mcp->event_id : ''] = $mc->MemberCardType->name;
      }
      
      // checks for each member card if it matches the rules set in the configuration
      $tickets = $this->getUser()->getTransaction()->Tickets;
      foreach ( $this->getUser()->getTransaction()->MemberCards as $mc )
      {
        $events = array();
        foreach ( $tickets as $tid => $ticket )
        {
          if ( isset($match[$ticket->price_id][$ticket->Manifestation->event_id])
            && $mc->MemberCardType->name == $match[$ticket->price_id][$ticket->Manifestation->event_id]
            || isset($match[$ticket->price_id][''])
            && $mc->MemberCardType->name == $match[$ticket->price_id][''] )
          {
            if ( !isset($events[$ticket->Manifestation->event_id]) )
              $events[$ticket->Manifestation->event_id] = 0;
            $events[$ticket->Manifestation->event_id]++;
            unset($tickets[$tid]); // using this trick, a ticket cannot be "used" twice
          }
        }
        
        // if the current cart does not match member cards prerequisites
        if ( count($events) < $nb[$mc->MemberCardType->name] )
        {
          $this->getContext()->getConfiguration()->loadHelpers('I18N');
          $this->getUser()->setFlash('error', $str = __('You need to book tickets for %%nb%% different events at least to be able to order a "%%mc%%" pass.', array('%%nb%%' => $nb[$mc->MemberCardType->name], '%%mc%%' => $mc->MemberCardType->description_name)));
          error_log('Transaction #'.$this->getUser()->getTransactionId().': '.$str);
          $this->redirect('transaction/show?id='.$this->getUser()->getTransactionId());
        }
      }
    }
    
    // surveys to apply
    $surveys = $this->getUser()->getTransaction()->getSurveysToFillIn();
    if ( $surveys->count() > 0 )
    {
      $this->getContext()->getConfiguration()->loadHelpers('I18N');
      $this->getUser()->setFlash('success', $str = __('You are about to complete your order, please fill in those surveys before...'));
      error_log('Transaction #'.$this->getUser()->getTransactionId().': '.$str);
      $this->redirect('cart/surveys');
    }
    
    // setting up the vars to commit to the bank
    $redirect = false;
    if ( ($topay = $this->transaction->getPrice(true,true)) > 0 && sfConfig::get('app_payment_type','paybox') != 'onthespot' )
    {
      if (!(
         class_exists($class = ucfirst($plugin = sfConfig::get('app_payment_type','paybox')).'Payment')
      && is_a($class, 'OnlinePaymentInterface', true)
      ))
        throw new liOnlineSaleException('You asked for a payment plugin ('.$plugin.') that does not exist or is not compatible.');
      $this->online_payment = $class::create($this->transaction);
    }
    else // no payment to be done
    {
      $this->getContext()->getConfiguration()->loadHelpers('I18N');
      
      $transaction = $this->transaction;
      if ( $transaction->BoughtProducts->count() == 0 && $transaction->Tickets->count() == 0 && $transaction->MemberCards->count() == 0 )
      {
        $this->getUser()->setFlash('notice', $str = __('Please control your order...'));
        error_log('Transaction #'.$this->getUser()->getTransactionId().': '.$str);
        $this->redirect('@homepage');
      }
      
      $transaction->Order[] = new Order;
      $this->createPaymentsDoneByMemberCards();
      $transaction->save();
      
      $this->sendConfirmationEmails($transaction, $this);
      $this->getUser()->resetTransaction();
      if ( $transaction->Payments->count() > 0 )
        $this->getUser()->setFlash('notice',__("Your command has been passed on your member cards, you don't have to pay anything."));
      elseif ( sfConfig::get('app_payment_type', 'paybox') == 'onthespot' )
        $this->getUser()->setFlash('notice',__("Your command has been booked, you will have to pay for it directly with us."));
      
      $redirect = 'transaction/show?id='.$transaction->id;
    }
    
    // empty'ing the password if asked for
    $vel = sfConfig::get('app_tickets_vel', array());
    if ( isset($vel['one_shot']) && $vel['one_shot'] )
    {
      $this->getUser()->getTransaction()->Contact->password = NULL;
      $this->getUser()->getTransaction()->Contact->save();
      error_log('Logout forced following the "one_shot" option.');
      $this->getUser()->logout();
    }
    
    if ( $redirect )
      $this->redirect($redirect);

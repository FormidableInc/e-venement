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
*    Copyright (c) 2006-2013 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2006-2013 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php

class myUser extends liGuardSecurityUser
{
  const CREDENTIAL_METAEVENT_PREFIX = 'event-metaevent-';
  const CREDENTIAL_WORKSPACE_PREFIX = 'event-workspace-';
  
  protected $metaevents = array();
  protected $workspaces = array();
  protected $transaction = NULL;
  protected $auth_exceptions = array();
  
  public function initialize(sfEventDispatcher $dispatcher, sfStorage $storage, $options = array())
  {
    parent::initialize($dispatcher, $storage, $options);
    $dispatcher->connect('pub.pre_execute', array($this, 'mustAuthenticate'));
    $dispatcher->connect('pub.before_showing_prices', array($this, 'checkAvailability'));
    $dispatcher->connect('pub.before_adding_tickets', array($this, 'checkAvailability'));
    $dispatcher->connect('pub.after_adding_tickets', array($this, 'addDefaultDirectContact'));
    
    if ( $this->getAttribute('online_store', NULL) === NULL
      || time() > strtotime($this->getAttribute('online_store_timeout', NULL)) )
    {
      $q = Doctrine::getTable('ProductCategory')->createQuery('pc')
        ->andWhere('pc.online = ?', true)
        ->leftJoin('pc.Products p')
        ->andWhereIn('p.meta_event_id IS NULL OR p.meta_event_id', array_keys($this->getMetaEventsCredentials()))
        ->andWhere('p.id IS NOT NULL')
        ->leftJoin('p.Declinations d')
        ->andWhere('d.id IS NOT NULL')
      ;
      $online_store = $q->count() > 0;
      $this->setAttribute('online_store', $online_store);
      $this->setAttribute('online_store_timeout', date('Y-m-d H:i:s', strtotime('+10 minutes')));
    }
  }
  
  public function addDefaultDirectContact(sfEvent $event)
  {
    if ( isset($event['direct_contact']) && $event['direct_contact'] === false )
      return;
    
    // detecting if a ticket has to be affected to the current contact
    $ticket = NULL;
    foreach ( $this->getTransaction()->Tickets as $tck )
    {
      if ( $tck->contact_id == $this->getContact()->id )
      {
        $ticket = NULL;
        break;
      }
      if ( is_null($tck->contact_id) )
        $ticket = $tck;
    }
    
    // adding the transaction's contact on the first coming ticket if needed
    if ( $ticket instanceof Ticket )
    {
      $ticket->DirectContact = $this->getContact();
      $ticket->save();
    }
  }
  
  public function checkAvailability(sfEvent $event)
  {
    $event->setReturnValue(true);
    $manifestation = $event['manifestation'];
    $vel = sfConfig::get('app_tickets_vel', array());
    $max = array();
    
    // controlling the global max_per_manifestation parameter
    $vel['max_per_manifestation'] = isset($vel['max_per_manifestation']) ? $vel['max_per_manifestation'] : 9;
    if ( !(isset($vel['no_online_limit_from_manifestations']) && $vel['no_online_limit_from_manifestations'])
      && $manifestation->online_limit && $manifestation->online_limit < $vel['max_per_manifestation'] )
      $vel['max_per_manifestation'] = $manifestation->online_limit;
    foreach ( $this->getTransaction()->Tickets as $ticket )
    if ( !$ticket->hasBeenCancelled() && $manifestation->id == $ticket->manifestation_id )
      $vel['max_per_manifestation']--;
    $max[] = $vel['max_per_manifestation'];
    if ( $vel['max_per_manifestation'] < 0 )
    {
      $event->setReturnValue(false);
      sfContext::getInstance()->getConfiguration()->loadHelpers(array('I18N'));
      $event['message'] = __('You cannot book a ticket on this date because you have already reached the limit of tickets for %%manif%%', array(
        '%%manif%%' => $ticket->Manifestation,
        '%%transaction%%' => $ticket->transaction_id
      ));
      $event->setReturnValue(false);
    }
    
    // controlling if there is any max_per_event_per_contact conflict
    $vel['max_per_event_per_contact'] = isset($vel['max_per_event_per_contact']) ? $vel['max_per_event_per_contact'] : false;
    if ( $vel['max_per_event_per_contact'] > 0 && $event->getReturnValue() )
    {
      $last_conflict = NULL;
      foreach ( $this->getContact()->Transactions as $transaction )
      foreach ( $transaction->Tickets as $ticket )
      if (( $ticket->printed_at || $ticket->integrated_at || $transaction->Order->count() > 0 || $ticket->transaction_id == $this->getTransaction()->id )
        && !$ticket->hasBeenCancelled()
        && $manifestation->id != $ticket->manifestation_id
        && $manifestation->event_id == $ticket->Manifestation->event_id
      )
      {
        $vel['max_per_event_per_contact']--;
        $last_conflict = $ticket;
      }
      $max[] = $vel['max_per_event_per_contact'];
      if ( $vel['max_per_event_per_contact'] <= 0 )
      {
        $event->setReturnValue(false);
        sfContext::getInstance()->getConfiguration()->loadHelpers(array('I18N'));
        $event['message'] = __('You cannot book a ticket on this date because you already have a ticket booked for %%manif%%', array(
          '%%manif%%' => $last_conflict->Manifestation,
        ));
        $event->setReturnValue(false);
      }
    }
    
    $event['max'] = min($max);
    
    // controlling if there is any time conflict
    if ( ($delay = sfConfig::get('app_tickets_no_conflict', false)) && $event->getReturnValue() )
    {
      $manifs = array();
      foreach ( $this->getContact()->Transactions as $transaction )
      foreach ( $transaction->Tickets as $ticket )
      if (( $ticket->transaction_id == $this->getTransaction()->id || $ticket->printed_at || $ticket->integrated_at || $transaction->Order->count() > 0 )
        && !$ticket->hasBeenCancelled()
        && ($manifestation->id != $ticket->manifestation_id || $ticket->transaction_id != $this->getTransaction()->id)
        && !isset($manifs[$ticket->manifestation_id])
      )
        $manifs[$ticket->manifestation_id] = $ticket;
      
      foreach ( $manifs as $ticket )
      {
        $start = strtotime('- '.$delay, strtotime($ticket->Manifestation->happens_at));
        $stop  = strtotime('+ '.$delay, strtotime($ticket->Manifestation->ends_at));
        if ( strtotime($manifestation->happens_at) >= $start && strtotime($manifestation->happens_at) <= $stop
          || strtotime($manifestation->ends_at)    >= $start && strtotime($manifestation->ends_at)    <= $stop
          || strtotime($manifestation->happens_at) <= $start && strtotime($manifestation->ends_at)    >= $stop )
        {
          sfContext::getInstance()->getConfiguration()->loadHelpers(array('I18N'));
          if ( $ticket->transaction_id == $this->getTransaction()->id )
            $event['message'] = __('You cannot book a ticket on this date because you already have a ticket booked for %%manif%%', array(
              '%%manif%%' => $ticket->Manifestation,
              '%%transaction%%' => $ticket->transaction_id
            ));
          else
            $event['message'] = __('You cannot book a ticket on this date because you already have a ticket booked for %%manif%% (in an other transaction #%%transaction%%)', array(
              '%%manif%%' => $ticket->Manifestation,
              '%%transaction%%' => $ticket->transaction_id
            ));
          $event->setReturnValue(false);
        }
      }
    }
  }
  
  public function isStoreActive()
  {
    return $this->getAttribute('online_store', false);
  }
  
  public function mustAuthenticate(sfEvent $event)
  {
    $sf_action = $event->getSubject();
    
    // the action it self
    if ( in_array(array($sf_action->getModuleName(), $sf_action->getActionName()), $this->auth_exceptions) )
      return;
    
    // the user...
    if (!( method_exists($sf_action, 'isAuthenticatingModule') && $sf_action->isAuthenticatingModule() ))
    {
      if ( !sfConfig::get('app_user_must_authenticate', false) )
        return;
      
      if ( $this->getTransaction()->contact_id )
        return;
    
      // for plateforms that require authenticated visitors
      $sf_action->forward('login','index');
    }
  }
  public function addAuthException($module, $action)
  {
    $this->auth_exceptions[] = array($module, $action);
    return $this;
  }
  
  public function getGuardUser()
  {
    if (!$this->user )
      $this->user = Doctrine::getTable('sfGuardUser')->retrieveByUsername(sfConfig::get('app_user_templating'));
    
    if (!$this->user)
    {
      // the user does not exist anymore in the database
      $this->signOut();
      
      throw new sfException('The user does not exist anymore in the database.');
    }
    
    return $this->user;
  }

  public function getWorkspacesCredentials() {
    $this->getGuardUser();
    if ( $this->workspaces )
      return $this->workspaces;
    
    $this->workspaces = array();
    
    if ( !$this->user )
      return $this->workspaces;
    
    foreach ( $this->user->Workspaces as $ws )
      $this->workspaces[$ws->id] = myUser::CREDENTIAL_WORKSPACE_PREFIX.$ws->id;
    
    return $this->workspaces;
  }
  public function getMetaEventsCredentials()
  {
    $this->getGuardUser();
    if ( $this->metaevents )
      return $this->metaevents;
    
    $this->metaevents = array();
    
    if ( !$this->user )
      return $this->metaevents;
    
    foreach ( $this->user->MetaEvents as $me )
      $this->metaevents[$me->id] = myUser::CREDENTIAL_METAEVENT_PREFIX.$me->id;
    
    return $this->metaevents;
  }
  
  public function getContact()
  {
    if ( is_null($this->getTransaction()->contact_id) )
      throw new liOnlineSaleException('Not yet authenticated.');
    
    return $this->getTransaction()->Contact;
  }
  public function hasContact()
  {
    return !is_null($this->getTransaction()->contact_id);
  }
  public function setContact(Contact $contact)
  {
    if ( !$contact->id )
      throw new liOnlineSaleException('Your contact is not yet recorded or does not fit the system requirements');
    
    $this->getTransaction()->Contact = $contact;
    foreach ( $this->getTransaction()->MemberCards as $mc )
      $mc->Contact = $contact;
    $this->getTransaction()->save();
    return $this;
  }
  
  public function getTransactionId()
  {
    if ( !$this->hasAttribute('transaction_id') )
    {
      $this->transaction = new Transaction;
      $this->dispatcher->notify(new sfEvent($this, 'pub.transaction_before_creation', array(
        'transaction' => $this->transaction,
        'user' => $this,
      )));
      
      $this->transaction->save();
      $this->setAttribute('transaction_id',$this->transaction->id);
      
      $this->dispatcher->notify(new sfEvent($this, 'pub.transaction_after_creation', array(
        'transaction' => $this->transaction,
        'user' => $this,
      )));
    }
    
    return $this->getAttribute('transaction_id');
  }
  public function getTransaction()
  {
    $tid = $this->getTransactionId();
    if ( $this->transaction instanceof Transaction )
      return $this->transaction;
      
    $q = Doctrine::getTable('Transaction')->createQuery('t')
      ->leftJoin('t.MemberCards tmc')
      ->leftJoin('t.Order o')
      ->leftJoin('tmc.MemberCardPrices tmcp')
      ->leftJoin('t.Contact c')
      ->leftJoin('c.Professionals p WITH p.id = t.professional_id')
      ->leftJoin('c.Transactions tr')
      ->leftJoin('c.MemberCards cmc ON c.id = cmc.contact_id AND (cmc.active = TRUE OR cmc.transaction_id = t.id)')
      ->leftJoin('cmc.MemberCardPrices cmcp')
      ->andWhere('t.id = ?',$tid);
    
    if ( $this->transaction = $q->fetchOne() )
      return $this->transaction;
    
    $this->logout();
    return $this->getTransaction();
  }
  
  public function resetTransaction()
  {
    $contact = false;
    try { $contact = $this->getContact(); }
    catch ( liOnlineSaleException $e ) { error_log($e->getMessage()); }
    
    $this->logout();
    if ( $contact )
      $this->setContact($contact);
  }
  public function logout()
  {
    if ( $this->transaction->Order->count() == 0 )
      $this->transaction->Tickets->delete();
    $this->getAttributeHolder()->remove('transaction_id');
    $this->transaction = NULL;
    $this->getTransaction();
  }
  
  public function setDefaultCulture(array $languages)
  {
    $cultures = array_keys(sfConfig::get('project_internals_cultures', array('fr' => 'Français')));
    
    if ( !$this->getAttribute('global_culture_forced', false) )
    {
      // all the browser's languages
      $user_langs = array();
      foreach ( $languages as $lang )
      if ( !isset($user_lang[substr($lang, 0, 2)]) )
        $user_langs[substr($lang, 0, 2)] = $lang;
      
      // comparing to the supported languages
      $done = false;
      foreach ( $user_langs as $culture => $lang )
      if ( in_array($culture, $cultures) )
      {
        $done = $culture;
        $this->setCulture($culture);
        break;
      }
      
      // culture by default
      if ( !$done )
        $this->setCulture($cultures[0]);
    }
    
    return $this;
  }
}

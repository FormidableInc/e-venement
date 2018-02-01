<?php

/**
 * cart actions.
 *
 * @package    symfony
 * @subpackage cart
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class cartActions extends sfActions
{
  protected $transaction = NULL;

  public function preExecute()
  {
    $this->getUser()->addAuthException($this->getModuleName(), 'response');
    $this->dispatcher->notify(new sfEvent($this, 'pub.pre_execute', array('configuration' => $this->configuration)));
    parent::preExecute();
  }
  public function executeCommitSurvey(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers('I18N');

    $params = $request->getParameter('survey', array());

    $s = new Survey;
    foreach ( $surveys = $this->getUser()->getTransaction()->getSurveysToFillIn() as $survey )
    if ( $survey->id == $params['id'] )
    {
      $s = $survey;
      break;
    }
    $this->form = new SurveyPublicForm($s);

    $this->form->bind($params);
    if ( !$this->form->isValid() )
      return 'Error';

    $this->form->save();
  }
  public function executeSurveys(sfWebRequest $request)
  {
    $this->forms = array();
    foreach ( $surveys = $this->getUser()->getTransaction()->getSurveysToFillIn() as $survey )
      $this->forms[] = new SurveyPublicForm($survey);
  }
  public function executeWidget(sfWebRequest $request)
  {
    try { $this->transac = $this->getUser()->getTransaction(); }
    catch ( liOnlineSaleException $e )
    { $this->transac = new Transaction; }

    if ( $this->transac === false )
      $this->transac = new Transaction;

    $this->timeout = false;

    // global timeout
    $time = strtotime(
      '+'.sfConfig::get('app_timeout_global', '1 hour'),
      strtotime($this->transac->created_at)
    ) - time();
    $this->global_timeout = $time <= 0 ? 'expired!' :
      floor($time/3600).':'.
      str_pad(floor($time%3600/60), 2, '0', STR_PAD_LEFT).':'.
      str_pad(floor($time%3600%60), 2, '0', STR_PAD_LEFT)
    ;
    if ( $time <= 0 )
    {
      $this->timeout = true;
      $this->getUser()->resetTransaction();
    }

    // older item timeout
    $ticket = Doctrine::getTable('Ticket')->createQuery('tck')
      ->andWhere('tck.transaction_id = ?', $this->transac->id)
      ->orderBy('tck.updated_at')
      ->fetchOne();
    $this->older_item_timeout = false;
    if ( $ticket )
    {
      $time = strtotime(
        '+'.sfConfig::get('app_timeout_item', '40 minutes'),
        strtotime($ticket->updated_at)
      ) - time();
      $this->older_item_timeout = $time <= 0 ? 'expired!' :
        floor($time/3600).':'.
        str_pad(floor($time%3600/60), 2, '0', STR_PAD_LEFT).':'.
        str_pad(floor($time%3600%60), 2, '0', STR_PAD_LEFT)
      ;
      if ( $time <= 0 )
      {
        $this->timeout = true;
        $this->getUser()->resetTransaction();
      }
    }
  }
  public function executeEmpty(sfWebRequest $request)
  {
    $this->getUser()->resetTransaction();
    $this->redirect('cart/show');
  }
  public function executeDone(sfWebRequest $request)
  {
    $this->dispatcher->notify(new sfEvent($this, 'pub.cart.done', array(
      'request' => $request,
      'action' => $this,
    )));

    try { $this->transaction = $this->getUser()->getTransaction(); }
    catch ( liOnlineSaleException $e )
    {
      throw new liEvenementException($e->message);
      $this->getContext()->getConfiguration()->loadHelpers('I18N');
      $this->getUser()->setFlash('error',__('No cart to display'));
      $this->redirect('cart/show');
    }
    
    // waits for the "response"
    sleep(2);

    // go back to the just-paid transaction, what ever it is
    $transaction = Doctrine::getTable('Transaction')->createQuery('t')
      ->select('t.*')
      ->andWhere('t.contact_id = ?', $this->transaction->contact_id)
      ->leftJoin('t.Payments p')
      ->andWhere('p.id IS NOT NULL')
      ->orderBy('p.created_at DESC')
      ->limit(1)
      ->fetchOne();
    if (! $transaction instanceof Transaction )
      $transaction = $this->getUser()->getTransaction();
    if ( $transaction->id == $this->getUser()->getTransactionId() )
      $this->getUser()->resetTransaction();
    $this->redirect('transaction/show?end=1&id='.$transaction->id);
  }
  public function executeCancel(sfWebRequest $request)
  {
    $this->getUser()->restoreCart();
    
    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    $this->getUser()->setFlash('error',__('You have just abandonned your payment, you can still empty / correct / validate your cart...'));
    $this->redirect('cart/show');
  }
  
  // Temp fix
  public function executeUndefined(sfWebRequest $request) {
      $this->redirect('event');
  }
  
  public function executeCgv(sfWebRequest $request) {
      $this->form = new sfForm;
      $this->getContext()->getConfiguration()->loadHelpers('I18N');
      $this->getResponse()->setTitle(__('Terms & Conditions').' - ', false);
      
      $this->form->url = pubConfiguration::getText('app_texts_terms_conditions_url');     
  }
  
  public function executeRegister(sfWebRequest $request)
  {
    // harden data
    $this->getContext()->getConfiguration()->hardenIntegrity();

    // Membercard availability
    $valid_tickets = 0;
    $transaction = $this->getUser()->getTransaction();
    
    foreach ($transaction->MemberCards as $mc)
    {
      $MC_events = array();
      foreach ($mc->MemberCardType->MemberCardPriceModels as $MC_price_model)
      {
        if ( $MC_price_model->event_id )
        {
          $MC_events[] = $MC_price_model->event_id;
        }
      }
      
      foreach ($transaction->Tickets as $ticket)
      {
        if ( count($MC_events) > 0 )
        {
          if ( in_array($ticket->Manifestation->event_id, $MC_events) )
          {
            $valid_tickets++;
          }
        }
        else
        {
          $valid_tickets++;
        }
      }
      
      if ( $valid_tickets < $mc->MemberCardType->nb_tickets_mini )
      {
        $this->getUser()->setFlash('error', __("You do not have enough ticket for the membercard ").$mc->MemberCardType->name);
        $this->redirect('transaction/show?id='.$this->getUser()->getTransactionId());
      }
    }

    // if every ticket needs a DirectContact
    if ( sfConfig::get('app_tickets_always_need_a_contact', false) && sfConfig::get('app_options_synthetic_plans', false)
      && in_array(NULL, $this->getUser()->getTransaction()->Tickets->toKeyValueArray('id', 'contact_id')) )
    {
      $this->getUser()->setFlash('error', __('You still have tickets without any contact information, and this is not allowed. Please fill in contact informations for each of your tickets.'));
      $this->redirect('transaction/show?id='.$this->getUser()->getTransactionId());
    }

    // pay a specific transaction
    $this->specific_transaction = intval($request->getParameter('transaction_id')).'' === ''.$request->getParameter('transaction_id','')
      ? Doctrine::getTable('Transaction')->find($request->getParameter('transaction_id'))
      : false;
    if ( $this->specific_transaction )
    {
      if ( $this->specific_transaction->contact_id != $this->getUser()->getTransaction()->contact_id )
        $this->specific_transaction = false;
      elseif ( $this->specific_transaction->id != $this->getUser()->getTransaction()->id )
      {
        $this->dispatcher->notify(new sfEvent($this->getUser(), 'pub.transaction_respawning', array(
          'configuration' => $this->configuration,
          'transaction'   => $this->specific_transaction,
        )));
      }
    }
    
    if ( in_array('liOnlineExternalAuthOpenIDConnectPlugin', $this->getContext()->getConfiguration()->getPlugins()) )
    {
      // The sales conditions have been accepted before coming here
      if ( !$this->getUser()->hasContact() ) 
      {
        $url = $this->generateUrl('default', array('module' => 'login', 'action' => 'index', 'register' => true));
        $this->redirect($url);
      }
    }

    // already done first
    if ( sfConfig::get('app_contact_modify_coordinates_first', false) && $this->getUser()->getContact() )
      $this->redirect('cart/order');

    $form_values = $this->getUser()->getAttribute('contact_form_values',array());
    unset($form_values['_csrf_token']);
    unset($form_values['id']);

    try { $contact = $this->getUser()->getContact() ? $this->getUser()->getContact() : new Contact; }
    catch ( liEvenementException $e )
    { $contact = new Contact; }

    if ( !isset($this->form) )
      $this->form = new ContactPublicForm($contact);

    if ( $contact->isNew() )
      $this->form->setDefaults($form_values);
    else
    {
      $pns = array();
      foreach ( $this->getUser()->getContact()->Phonenumbers as $pn )
        $pns[$pn->updated_at.' '.$pn->id] = $pn;
      ksort($pns);

      $pn = array_pop($pns);
      if ( isset($pn) )
      {
        $this->form->setDefault('phone_type',$pn->name);
        $this->form->setDefault('phone_number',$pn->number);
      }

      $this->form->removePassword();
    }

    $this->login = new LoginForm;
  }

  public function executeShow(sfWebRequest $request)
  {
    $this->dispatcher->notify(new sfEvent($this, 'pub.cart_show', array(
      'transaction' => $this->getUser()->getTransaction(),
      'user' => $this->getUser(),
    )));
    
    // harden data
    $this->getContext()->getConfiguration()->hardenIntegrity();

    // normal behavior
    $this->transaction_id = $this->getUser()->getTransaction()->id;

    $this->transaction = $this->getUser()->getTransaction();

    if ( $this->transaction->Tickets->count() == 0
      && $this->transaction->MemberCards->count() == 0
      && $this->transaction->BoughtProducts->count() == 0 )
    {
      $this->getContext()->getConfiguration()->loadHelpers('I18N');
      $this->getUser()->setFlash('notice',__('Your cart is still empty, select tickets first...'));
      $this->redirect('@homepage');
    }

    $this->redirect('transaction/show?id='.$this->transaction_id);
  }

  public function executeOrder(sfWebRequest $request)
  {
    require(dirname(__FILE__).'/order.php');
  }

  public function executeOnthespot(sfWebRequest $request)
  {
    require(dirname(__FILE__).'/onthespot.php');
  }

  public function executeResponse(sfWebRequest $request)
  {
    // WHERE THE BANK PINGS BACK WHEN THE ORDER IS PAID
    return require(dirname(__FILE__).'/response.php');
  }

  protected function getMemberCardPaymentMethod()
  {
    return Doctrine::getTable('PaymentMethod')->createQuery('pm')
      ->andWhere('pm.member_card_linked = ?',true)
      //->andWhere('pm.display = ?',true)
      ->orderBy('id')
      ->fetchOne();
  }

  public function executeSendConfirmationEmails(sfWebRequest $request)
  {
    $this->sendConfirmationEmails($this->getUser()->getTransaction(), $this);
    return sfView::NONE;
  }
  public static function sendConfirmationEmails(Transaction $transaction, sfAction $action, $tokened = false)
  {
    return require(dirname(__FILE__).'/send-confirmation-emails.php');
  }

  protected function createPaymentsDoneByMemberCards(PaymentMethod $payment_method = NULL)
  {
    if ( is_null($payment_method) )
      $payment_method = $this->getMemberCardPaymentMethod();
    if ( !$payment_method )
      throw new liOnlineSaleException('Online Sales: No payment method defined for member cards.');

    foreach ( $this->getUser()->getTransaction()->Tickets as $ticket )
    if ( $ticket->Price->member_card_linked )
    {
      $p_mc = new Payment;
      $p_mc->value = $ticket->value;
      $p_mc->Method = $payment_method;

      foreach ( $this->getUser()->getTransaction()->MemberCards as $mc )
      if ( $mc->hasPrice($ticket->price_id) && $mc->getValue() >= $ticket->value )
        $p_mc->member_card_id = $mc->id;

      if ( is_null($p_mc->member_card_id) )
      foreach ( $this->getUser()->getContact()->MemberCards as $mc )
      if ( $mc->transaction_id != $this->transaction->id && $mc->active
        && $mc->hasPrice($ticket->price_id) && $mc->getValue() >= $ticket->value )
        $p_mc->member_card_id = $mc->id;

      if ( !is_null($p_mc->member_card_id) )
        $this->getUser()->getTransaction()->Payments[] = $p_mc;
    }
  }
  
  protected function oneShot()
  {
    // empty'ing the password if asked for
    $vel = sfConfig::get('app_tickets_vel', array());
    if (!( isset($vel['one_shot']) && $vel['one_shot'] ))
      return false;
    
    $this->transaction->Contact->password = NULL;
    $this->transaction->Contact->save();
    error_log('Logout forced following the "one_shot" option.');
    $this->getUser()->logout();
    
    return true;
  }


    public function getErrors($form = false, $embedded_forms = array()) {
      if (!$form){
        return false;
      }
      $errors = array();  $total_error++;
      // individual widget errors
      foreach ($form as $form_field) {
        if ($form_field->hasError()) {
          $error_obj = $form_field->getError();
          if ($error_obj instanceof sfValidatorErrorSchema) {
            foreach ($error_obj->getErrors() as $error) {
              // add namespace for embedded form erros
              if ($form->getName() != $form->getName()) {
                $errors[$form->getName()][$form_field->getName()][] = $error->getMessage();  $total_error++;
              } else {
                $errors[$form_field->getName()][] = $error->getMessage();
              }
            }
          } else {
            if ($form->getName() != $form->getName()) {
              $errors[$form->getName()][$form_field->getName()][] = $error_obj->getMessage();  $total_error++;
            } else {
              $errors[$form_field->getName()] = $error_obj->getMessage();  $total_error++;
            }
          }
        }
      }
      // for global errors
      foreach ($form->getGlobalErrors() as $validator_error) {
        $errors[] = $validator_error->getMessage();
      }
      // for embedded form error processing
      $count_embedded_error = 0;
      if (count($embedded_forms) && is_array($embedded_forms) ) {
        foreach($embedded_forms as $key => $embedded_form_name){
          if (isset($errors[$embedded_form_name])) {
            if(is_array($errors[$embedded_form_name])){
              foreach($errors[$embedded_form_name] as $key1=>$errors_embedded){
                $error_embedded_form = array();
                $asEFRawErrors = explode("]", $errors_embedded);
                foreach($asEFRawErrors as $ssRawError){
                  if ($ssRawError!=null) {
                    $raw_error = explode("[",$ssRawError);
                    $error_embedded_form[trim($raw_error[0])] = trim($raw_error[1]);  $total_error++;
                    //$error_embedded_form[] = $ssRawError;
                  }
                }
                $errors[$embedded_form_name][$key1] = $error_embedded_form;
                $count_embedded_error += count($error_embedded_form);
              }
            }
          }
        }
      }
      $errors_final['error_message'] = $errors;
      // count errors
      $errors_final['error_count'] = $total_error;
      return $errors_final;
    }
}

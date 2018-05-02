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
*    Copyright (c) 2006-2014 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2006-2014 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php

require_once dirname(__FILE__).'../../../../config/autoload.inc.php';

class tckConfiguration extends sfApplicationConfiguration
{
  protected $collectors = array();
  protected $task;
  protected $init_configuration = false;
  
  public function setup()
  {
    if (!( sfContext::hasInstance() && get_class(sfContext::getInstance()->getConfiguration()) != get_class($this) ))
      $this->enablePlugins(array('liClassLoaderPlugin', 'liPDFPlugin', 'sfDomPDFPlugin', 'liBarcodePlugin'));
    parent::setup();
  }
  public function configure()
  {
    parent::configure();
    sfConfig::set('sf_app_template_dir', sfConfig::get('sf_apps_dir') . '/templates');
  }
  public function initialize()
  {
    $conf = sfConfig::get('app_transaction_email', array());
    
    $this->dispatcher->connect('user.change_authentication', array($this, 'logAuthentication'));
    $this->dispatcher->connect('tck.tickets_print', array($this, 'sendEmailOnPrintingTickets'));
    
    if ( isset($conf['products']) && !in_array($conf['products'], array('never', false)) )
      $this->dispatcher->connect('tck.products_integrate', array($this, 'sendEmailOnIntegratingProducts'));
    
    if ( isset($conf['always_send_confirmation']) && $conf['always_send_confirmation'] )
      $this->dispatcher->connect('tck.before_transaction_creation', array($this, 'activateConfirmationEmails'));
    
    if ( isset($conf['force_send_confirmation']) && $conf['force_send_confirmation'] )
      $this->dispatcher->connect('tck.before_transaction_respawning', array($this, 'activateConfirmationEmails'));
    
    if ( sfConfig::get('app_tickets_auto_integrate', false) )
      $this->dispatcher->connect('tck.before_trying_to_close_transaction', array($this, 'productsIntegration'));
    
    if (!( sfContext::hasInstance() && get_class(sfContext::getInstance()->getConfiguration()) != get_class($this) ))
      $this->enableSecondWavePlugins($arr = sfConfig::get('app_options_plugins', array()));
    ProjectConfiguration::initialize();
  }
  
  public function productsIntegration($event)
  {
    if (!( isset($event['transaction']) && $event['transaction'] instanceof Transaction ))
      return;
    $timeout = sfConfig::get('app_tickets_timeout', array());
    
    $cpt = 0;
    $paid = $event['transaction']->getPaid();
    foreach ( $event['transaction']->getItemables() as $pdt )
    if ( !$pdt->isSold() && !( $pdt instanceof Ticket && $pdt->auto_by_hold )) // if something has to be done
    // we go if:
    //     - the pdt's value can be paid by the Payments
    //       OR its value == 0 and there is an order in the Transaction
    // AND - the pdt is not a Ticket that requires a Seat
    // AND - the pdt is not a Ticket with no Price linked
    if ( $pdt->value == 0 && $pdt->Transaction->Order->count() > 0
      || $pdt->value > 0 && $event['transaction']->getPrice(false, true) < $paid )
    if (!( $pdt instanceof Ticket && $pdt->needsSeating() ))
    if (!( $pdt instanceof Ticket && is_null($pdt->price_id) ))
    {
      $pdt->integrated_at = date('Y-m-d H:i:s'); // integrate
      $cpt++;
    }
    
    $event->setReturnValue($cpt);
  }
  
  // force sending emails on every transactions, depends on app.yml parameters
  public function activateConfirmationEmails($event)
  { try {
    $event['transaction']->send_an_email = true;
  } catch ( Exception $e ) { return $this->catchError($e); } }
  
  public function sendEmailOnPrintingTickets(sfEvent $event)
  {
    $this->genericSendEmailOn($event);
  }

  public function sendEmailOnIntegratingProducts(sfEvent $event)
  {
    $this->genericSendEmailOn($event);
  }
  
  public function genericSendEmailOn(sfEvent $event)
  { try {
    $conf = sfConfig::get('app_transaction_email', array());
    $transaction = $event['transaction'];
    
    if ( !$transaction->send_an_email
      || !(isset($conf['always_send_confirmation']) && $conf['always_send_confirmation'])
      || !($transaction->professional_id && $transaction->Professional->contact_email) && !($transaction->contact_id && $transaction->Contact->email)
    )
      throw new liEvenementException('You have tried to send an email without the ability for (no contact_id, no professional_id, no "send_an_email" field set to "true")...');
    
    // a trick using the "pub" application to send the email
    sfContext::getInstance()->getConfiguration()->loadHelpers('CrossAppLink');
    file_get_contents(str_replace('https://','http://',cross_app_url_for(
      'pub',
      'transaction/sendEmail?id='.$event['transaction']->id.'&token='.md5($event['transaction']->id.'|*|*|'.sfConfig::get('project_eticketting_salt', 'e-venement')),
      true
    )));
  } catch ( Exception $e ) { return $this->catchError($e); } }
  
  public function logAuthentication(sfEvent $event)
  {
    $params   = $event->getParameters();
    $user     = sfContext::getInstance()->getUser();
    $request  = sfContext::getInstance()->getRequest();
    
    if ( !is_object($user) )
      return false;
    
    if (( sfConfig::get('project_login_alert_beginning_at', false) && sfConfig::get('project_login_alert_beginning_at') < time() || !sfConfig::get('project_login_alert_beginning_at', false) )
      &&( sfConfig::get('project_login_alert_ending_at', false) && sfConfig::get('project_login_alert_ending_at') > time() || !sfConfig::get('project_login_alert_ending_at', false) )
      && sfConfig::get('project_login_alert_message', false) )
      $user->setFlash('error', sfConfig::get('project_login_alert_message'));

    $auth = new Authentication();
    $auth->sf_guard_user_id = $user->getId();
    $auth->description      = $user;
    $auth->ip_address       = $request->getHttpHeader('addr','remote');
    $auth->user_agent       = $request->getHttpHeader('User-Agent');
    $auth->referer          = $request->getReferer();
    $auth->success          = $params['authenticated'];
    
    $auth->save();
  }
  
  public function initGarbageCollectors(sfCommandApplicationTask $task = NULL)
  {
    $this->task = $task;
    
    // WIP tickets collector
    $this->addGarbageCollector('wip', function(){
      $timeout = sfConfig::get('app_tickets_timeout', array());
      $section = 'WIP tickets';
      $this->stdout($section, 'Resetting seats...', 'COMMAND');
      $tickets = Doctrine_Query::create()->from('Ticket tck')
        ->andWhere('tck.price_id IS NULL')
        ->andWhere('tck.seat_id IS NOT NULL')
        ->andWhere('tck.printed_at IS NULL AND tck.integrated_at IS NULL AND tck.cancelling IS NULL')
        ->andWhere('tck.updated_at < ?', date('Y-m-d H:i:s', strtotime(($timeout['wip'] ? $timeout['wip'] : '2 hours').' ago')))
        
        ->leftJoin('tck.Transaction t')
        ->leftJoin('t.Order o')
        ->andWhere('o.id IS NULL')
      ;
      
      // Domain segmentation
      if ( ($dom = sfConfig::get('project_internals_users_domain', false)) && $dom != '.' )
      $tickets->leftJoin("t.User tvu ON tvu.id = (SELECT tv.sf_guard_user_id FROM TransactionVersion tv WHERE tv.id = t.id AND tv.version = 1)")
        ->leftJoin('tvu.Domain uvd')
        ->andWhere('uvd.name ILIKE ? OR uvd.name = ?', array('%.'.$dom, $dom));
      
      $tickets->execute();
      $nb = $tickets->count();
      foreach ( $tickets as $ticket )
      {
        $ticket->seat_id = NULL;
        $ticket->save();
      }
      $this->stdout($section, "[OK] $nb tickets resetted", 'INFO');
    });
    
    
    // Close transaction and try to auto-integrate tickets if setted up
    $this->addGarbageCollector('auto-integrate', function(){
      $section = 'Auto-integration';
      $this->stdout($section, 'Integrating what we can...', 'COMMAND');
      $timeout = sfConfig::get('app_tickets_timeout', array());
      
      $q = Doctrine::getTable('Transaction')->createQuery('t', NULL, true)
        ->select('t.id, t.closed')
        ->leftJoin('t.Payments p')
        ->andWhere('t.updated_at < ?', $date = date('Y-m-d H:i:s', strtotime(($timeout['asked'] ? $timeout['asked'] : '1 hour').' - 1 minutes ago')))
        ->andWhere('t.closed = ?', false)
        ->orderBy('t.id')
      ;
      
      // Domain segmentation already in the transaction query
            
      $cpt = $closed = 0;
      foreach ( $q->execute() as $transaction )
      {
        if ( sfConfig::get('app_tickets_auto_integrate', false) )
        {
          $this->dispatcher->notify($event = new sfEvent($this, 'tck.before_trying_to_close_transaction', array(
            'transaction' => $transaction,
            'user'        => NULL,
          )));
        }
          
        $semaphore = array('products' => true, 'amount' => 0);
        foreach ( $transaction->getItemables() as $pdt )
        if ( !$pdt->isSold() )
        {
          $semaphore['products'] = false;
          break;
        }
        if ( $semaphore['products'] && ($semaphore['amount'] = $transaction->getPaid() - $transaction->getPrice(true,true)) == 0 )
        {
          $transaction->closed = true;
          $transaction->automatic = true;
          $closed++;
          $this->stdout($section, 'Transaction #'.$transaction->id.' closed by garbage collector.', 'INFO');
        }
        
        if ( $transaction->isModified(true) )
        try
        {
          $transaction->save();
          if ( $event && $event->getReturnValue() > 0 )
          {
            $cpt += $event->getReturnValue();
            $this->stdout($section, $event->getReturnValue().' itemables integrated for transaction #'.$transaction->id, 'DEBUG');
          }
        }
        catch ( Exception $e )
        { $this->stdout($section, '[KO] the transaction #'.$transaction->id.' cannot be auto-integrated: '.$e->getMessage(), 'ERROR'); }
      }
      $this->stdout($section, "[OK] globally, $cpt itemables were integrated (and $closed transactions closed)", 'INFO');
    });
    
    // Asked tickets collector
    $this->addGarbageCollector('asked', function(){
      $timeout = sfConfig::get('app_tickets_timeout', array());
      $section = 'Asked tickets';
      $this->stdout($section, 'Resetting seats on squatters...', 'COMMAND');
      $q = Doctrine_Query::create()->from('Ticket tck')
        ->andWhere('tck.price_id IS NOT NULL')
        ->andWhere('tck.printed_at IS NULL AND tck.integrated_at IS NULL AND tck.cancelling IS NULL AND tck.duplicating IS NULL')
        ->andWhere('tck.updated_at < ?', $date = date('Y-m-d H:i:s', strtotime(($timeout['asked'] ? $timeout['asked'] : '1 hour').' ago')))
        ->andWhere('tck.seat_id IS NOT NULL')
        ->leftJoin('tck.Transaction t')
        ->leftJoin('t.Order o')
        ->andWhere('o.id IS NULL')
        ->select('tck.*')
      ;
      
      // Domain segmentation
      if ( ($dom = sfConfig::get('project_internals_users_domain', false)) && $dom != '.' )
      $q->leftJoin("t.User tvu ON tvu.id = (SELECT tv.sf_guard_user_id FROM TransactionVersion tv WHERE tv.id = t.id AND tv.version = 1)")
        ->leftJoin('tvu.Domain uvd')
        ->andWhere('uvd.name ILIKE ? OR uvd.name = ?', array('%.'.$dom, $dom));
      
      $tickets = $q->execute();
      $nb = $tickets->count();
      foreach ( $tickets as $ticket )
      {
        $ticket->seat_id = NULL;
        $ticket->save();
      }
      $this->stdout($section, "[OK] $nb tickets resetted", 'INFO');
    });
    
    // Asked products collector
    $this->addGarbageCollector('products', function(){
      $timeout = sfConfig::get('app_tickets_timeout', array());
      $section = 'Asked products';
      $this->stdout($section, 'Deleting too old products...', 'COMMAND');
      $q = Doctrine_Query::create()->from('BoughtProduct bp')
        ->andWhere('bp.integrated_at IS NULL')
        ->andWhere('bp.updated_at < ?', $date = date('Y-m-d H:i:s', strtotime(($timeout['asked'] ? $timeout['asked'] : '1 hour').' ago')))
        ->leftJoin('bp.Transaction t')
        ->leftJoin('t.Order o')
        ->select('bp.id')->groupBy('bp.id')
        ->having('count(o.id) = 0')
      ;
      
      // Domain segmentation
      if ( ($dom = sfConfig::get('project_internals_users_domain', false)) && $dom != '.' )
      $q->leftJoin("t.User tvu ON tvu.id = (SELECT tv.sf_guard_user_id FROM TransactionVersion tv WHERE tv.id = t.id AND tv.version = 1)")
        ->leftJoin('tvu.Domain uvd')
        ->andWhere('uvd.name ILIKE ? OR uvd.name = ?', array('%.'.$dom, $dom));
      
      $pdts = $q->execute();
      $nb = $pdts->count();
      $pdts->delete();
      $this->stdout($section, "[OK] $nb products deleted", 'INFO');
    });
    
    // Close useless transactions
    $this->addGarbageCollector('close', function(){
      $timeout = sfConfig::get('app_tickets_timeout', array());
      $section = 'Opened transactions';
      $this->stdout($section, 'Closing too old transactions...', 'COMMAND');
      
      $q = Doctrine::getTable('Transaction')->createQuery('t', 'printed', true)
        ->select('t.id, t.closed')
        ->leftJoin('t.Payments p')
        ->andWhere('t.updated_at < ?', date('Y-m-d H:i:s', strtotime('1 day ago')))
        ->andWhere('t.closed = ?', false)
        ->leftJoin('t.Order o')
        ->groupBy('t.id, t.closed')
        ->having('count(tck.id) = 0 AND count(p.id) = 0 AND count(bp.id) = 0 AND count(o.id) = 0')
      ;
      
      // Domain segmentation already in th transaction query
      
      $transactions = $q->execute();
      $cpt = 0;
      foreach ( $transactions as $transaction ) try
      {
        $this->dispatcher->notify($event = new sfEvent($this, 'tck.before_trying_to_close_transaction', array(
          'transaction' => $transaction,
          'user'        => NULL,
        )));
        
        $transaction->closed = true;
        $transaction->save();
        $cpt++;
      }
      catch ( Exception $e )
      {
        $this->stdout($section, '[KO] the transaction #'.$transaction->id.' cannot be closed: '.$e->getMessage(), 'ERROR');
      }
      
      $this->stdout($section, '[OK] '.$cpt.' transactions closed', 'INFO');
    });
    
    return $this;
  }
}

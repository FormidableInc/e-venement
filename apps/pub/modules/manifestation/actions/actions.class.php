<?php

require_once dirname(__FILE__).'/../lib/manifestationGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/manifestationGeneratorHelper.class.php';

/**
 * manifestation actions.
 *
 * @package    symfony
 * @subpackage manifestation
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class manifestationActions extends autoManifestationActions
{
  public function preExecute()
  {
    $this->dispatcher->notify(new sfEvent($this, 'pub.pre_execute', array('configuration' => $this->configuration)));
    parent::preExecute();
  }
  
  public function executeIcal(sfWebRequest $request)
  {
    $this->executeShow($request);
  }
  
  public function executeIndex(sfWebRequest $request)
  {
    if ( $request->hasParameter('mc_pending') )
      $this->setFilters(array());
    
    if ( $this->getPager()->getQuery()->count() == 1 && !$request->hasParameter('debug') )
    {
      $manifestation = $this->getPager()->getQuery()->select('m.id')->fetchOne();
      
      foreach ( array('success', 'notice', 'error') as $type )
      if ( $this->getUser()->getFlash($type) )
        $this->getUser()->setFlash($type, $this->getUser()->getFlash($type));
      
      $this->redirect('manifestation/show?id='.$manifestation->id);
    }
    
    $filters = $this->getFilters();
    if ( isset($filters['event_id']) && $filters['event_id'] )
    {
      $event = Doctrine::getTable('Event')->createQuery('e')->andWhere('e.id = ?', $filters['event_id'])->fetchOne();
      sfConfig::set('pub.meta_event.slug', $event->MetaEvent->slug);
    }

    parent::executeIndex($request);

    $this->getResponse()->setTitle($event.' - ', false);

    // focusing on member card pending tickets
    $transaction = $this->getUser()->getTransaction();
    if ( $request->hasParameter('mc_pending') && $transaction->MemberCards->count() > 0 )
    {
      $events = array();
      foreach ( $transaction->MemberCards as $mc )
      foreach ( $mc->MemberCardType->MemberCardPriceModels as $mcpm )
      if ( !$mcpm->autoadd )
        $events[] = $mcpm->event_id;
      
      $this->pager->getQuery()
        ->andWhereIn('e.id', $events);
      $vel = sfConfig::get('app_tickets_vel', array());
      $vel['display_tickets_in_manifestations_list'] = true;
      sfConfig::set('app_tickets_vel', $vel);
    }
  }
  public function executeDel(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    $q = Doctrine::getTable('Ticket')->createQuery('tck')
      ->andWhere('tck.transaction_id = ?', $this->getUser()->getTransactionId())
      ->andWhere('tck.integrated_at IS NULL AND tck.printed_at IS NULL AND tck.cancelling IS NULL')
      ->andWhere('tck.gauge_id = ?', $request->getParameter('gauge_id',0))
    ;
    if ( intval($request->getParameter('price_id',0)) > 0 )
      $q->andWhere('tck.price_id = ?', intval($request->getParameter('price_id',0)));
    $this->forward404Unless($tickets = $q->execute());
    $tickets->delete();
    $this->getUser()->setFlash('success', __('Your items were successfully removed from your cart.'));
    $this->redirect('cart/show');
  }

  public function executeBatchDelete(sfWebRequest $request)
  {
    $this->redirect('manifestation/index');
  }
  public function executeCreate(sfWebRequest $request)
  {
    $this->redirect('manifestation/index');
  }
  public function executeNew(sfWebRequest $request)
  {
    $this->redirect('manifestation/index');
  }
  public function executeUpdate(sfWebRequest $request)
  {
    $manif = $request->getParameter('manifestation');
    $this->redirect('manifestation/show?id='.$manif['id']);
  }
  public function executeEdit(sfWebRequest $request)
  {
    $this->redirect('manifestation/show?id='.$request->getParameter('id'));
  }
  public function executeShow(sfWebRequest $request)
  {
    $vel = sfConfig::get('app_tickets_vel', array());
    $mes = array();
    foreach ( $this->getUser()->getMetaEventsCredentials() as $meid => $c )
      $mes[$meid] = '?';
    
    $q = Doctrine::getTable('Gauge')->createQuery('g')
      ->addSelect('gtck.*, m.*, mpm.*, mp.*, gpg.*, gp.*, tck.*, e.*, l.*, ws.*, sp.*, op.*')
      
      ->leftJoin('g.Tickets gtck WITH gtck.price_id IS NULL AND gtck.seat_id IS NOT NULL AND gtck.transaction_id = ?', $this->getUser()->getTransaction()->id)
      ->leftJoin('g.Manifestation m WITH m.id = ? AND m.reservation_confirmed = ?', array($request->getParameter('id'), true))
      
      ->leftJoin('m.IsNecessaryTo int')
      //->leftJoin('g.Workspace ws')
      ->leftJoin('ws.Users wu WITH wu.id = ?', $this->getUser()->getId())
      ->leftJoin('m.Location l')
      ->leftJoin('l.SeatedPlans sp')
      ->leftJoin('sp.Workspaces spws')
      ->leftJoin('m.Event e')
      ->leftJoin('e.MetaEvent me WITH me.id IN ('.implode(',',$mes).')', array_keys($mes))
      ->leftJoin('g.PriceGauges gpg')
      ->leftJoin('gpg.Price gp')
      ->leftJoin('gp.Translation gpt WITH gpt.lang = ?', $this->getUser()->getCulture())
      ->leftJoin('gp.Tickets ggtck WITH ggtck.gauge_id = g.id AND ggtck.transaction_id = ?', $this->getUser()->getTransaction()->id)
      ->leftJoin('m.PriceManifestations mpm')
      ->leftJoin('mpm.Price mp')
      ->leftJoin('mp.Translation mpt WITH mpt.lang = ?', $this->getUser()->getCulture())
      ->leftJoin('mp.Tickets tck WITH tck.gauge_id = g.id AND tck.transaction_id = ?', $this->getUser()->getTransaction()->id)
      
      ->leftJoin('gp.Users gpu WITH gpu.id = ?', $this->getUser()->getId())
      ->leftJoin('gp.Workspaces gpw WITH gpw.id = g.workspace_id AND g.manifestation_id = ?', $request->getParameter('id'))
      ->leftJoin('mp.Users mpu WITH mpu.id = ?', $this->getUser()->getId())
      ->leftJoin('mp.Workspaces mpw WITH mpw.id = g.workspace_id AND g.manifestation_id = ?', $request->getParameter('id'))
      
      ->andWhere('g.online = ?', true)
      ->andWhere('g.manifestation_id = ?', $request->getParameter('id'))
      ->andWhere('m.id IS NOT NULL')
      ->andWhere('(m.happens_at > NOW() OR ?)', sfConfig::get('sf_web_debug', false))
      
      ->andWhere('mpu.id IS NOT NULL AND mpw.id IS NOT NULL OR gpu.id IS NOT NULL AND gpw.id IS NOT NULL')
      ->andWhere('wu.id IS NOT NULL')
      
      ->orderBy('g.group_name, ws.name, gpg.value DESC, mpm.value DESC, gpt.name, mpt.name')
    ;
    $this->gauges = $q->execute();
    
    if (!( $this->gauges && $this->gauges->count() > 0 ))
    {
      error_log('manifestation/show: no gauge available');
      $this->getContext()->getConfiguration()->loadHelpers('I18N');
      $this->getUser()->setFlash('error',__('Date unavailable, try an other one.'));
      $this->redirect('event/index?meta-event='.sfConfig::get('pub.meta_event.slug',''));
    }
    
    $this->manifestation = $this->gauges[0]->Manifestation;
    $this->form = new PricesPublicForm;
    
    if ( $this->manifestation->IsNecessaryTo->count() > 0 )
      $this->redirect('manifestation/show?id='.$this->manifestation->IsNecessaryTo[0]->id);
    
    sfConfig::set('pub.meta_event.slug', $this->manifestation->Event->MetaEvent->slug);
    
    $this->mcp = $this->getAvailableMCPrices($this->manifestation);
    
    // if it is useless to use the "synthetic plan" features
    if ( !sfConfig::get('app_options_synthetic_plans', false) )
      $this->use_synthetic_plans = false;
    else
    {
      foreach ( $this->gauges as $gauge )
      {
        $gauge->Manifestation = $this->manifestation; // caching data
        $this->use_synthetic_plans = true;
        if ( !$gauge->getSeatedPlan() )
        {
          $this->use_synthetic_plans = false;
          break;
        }
      }
    }
    
    $this->getResponse()->setTitle($this->manifestation->Event.' - ', false);
    
    if ( strtotime('now + '.sfConfig::get('app_tickets_close_before','36 hours')) > strtotime($this->manifestation->happens_at) )
      return 'Closed';
  }
  
  protected function getAvailableMCPrices(Manifestation $manifestation = NULL)
  {
    $mcp = array();
    try {

    $mcs = $this->getUser()->getTransaction()->contact_id
      ? $this->getUser()->getContact()->MemberCards
      : $this->getUser()->getTransaction()->MemberCards;
    if ( $mcs->count() == 0 )
      return $mcp;
    
    // get back available prices
    foreach ( $mcs as $mc )
    if ( $mc->active || $mc->transaction_id == $this->getUser()->getTransactionId() )
    foreach ( $mc->MemberCardPrices as $price )
    {
      $event_id = is_null($price->event_id) ? '' : $price->event_id;
      
      if ( $event_id && $manifestation instanceof Manifestation && $price->event_id != $manifestation->event_id )
        continue;
      
      if ( !isset($mcp[$price->price_id][$event_id]) )
        $mcp[$price->price_id][$event_id] = 0;
      
      $mcp[$price->price_id][$event_id]++;
    }
    
    // get back already booked tickets
    $q = Doctrine_Query::create()->from('Ticket tck')
      ->select('tck.*, m.event_id AS event_id')
      ->andWhere('tck.printed_at IS NULL')
      ->andWhere('tck.member_card_id IS NOT NULL OR t.id = ?', $this->getUser()->getTransactionId())
      ->leftJoin('tck.Manifestation m')
      ->leftJoin('m.Event e')
      ->leftJoin('e.Manifestations em')
      ->leftJoin('tck.Price p')
      ->andWhere('p.member_card_linked = ?',true)
      ->leftJoin('tck.Transaction t')
      ->andWhere('t.contact_id = ?',$this->getUser()->getContact()->id)
      ->leftJoin('t.Order o')
    ;
    if ( $manifestation )
      $q->andWhere('em.id = ?', $manifestation->id)
        ->andWhere('o.id IS NOT NULL OR t.id = ? AND tck.manifestation_id != em.id', $this->getUser()->getTransactionId())
      ;
    else
      $q->andWhere('o.id IS NOT NULL OR t.id = ?', $this->getUser()->getTransactionId());
      
    $tickets_to_count = $q->execute();
    foreach ( $tickets_to_count as $ticket )
    {
      if ( isset($mcp[$ticket->price_id][$ticket->event_id]) )
        $mcp[$ticket->price_id][$ticket->event_id]--;
      else
        $mcp[$ticket->price_id]['']--;
    }
    
    return $mcp;
    
    }
    catch ( liEvenementException $e )
    { return $mcp; }
  }
}

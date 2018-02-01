<?php

/**
 * prices actions.
 *
 * @package    e-venement
 * @subpackage prices
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class pricesActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfRequest $request A request object
  */
  public function executeIndex(sfWebRequest $request)
  {
    if ( $request->hasParameter('criterias') )
    {
      $this->criterias = $request->getParameter('criterias');
      $this->getUser()->setAttribute('stats.criterias',$this->criterias,'admin_module');
      $this->redirect($this->getContext()->getModuleName().'/index');
    }
    
    $this->form = new StatsCriteriasForm();
    $this->form
      ->addUsersCriteria()
      ->addEventCriterias()
      ->addManifestationCriteria()
      ->addWeekDayCriteria()
    ;
    if ( is_array($this->getUser()->getAttribute('stats.criterias',array(),'admin_module')) )
      $this->form->bind($this->getUser()->getAttribute('stats.criterias',array(),'admin_module'));
  }
  
  public function executeJson(sfWebRequest $request)
  {
    $param = $request->getParameter('id');
    $this->lines = $this->getPrices($param == 'asked', $param == 'ordered', $param == 'all', 'array');
    $total = 0;

    foreach ( $this->lines as $line )
      $total += $line['nb'];
    
    foreach ( $this->lines as $key => $line )
      $this->lines[$key]['percent'] = number_format(round($line['nb']*100/$total,2));
  }
  
  protected function getPrices($asked = false, $ordered = false, $all = false, $type = NULL)
  {
    $criterias = $this->getUser()->getAttribute('stats.criterias',array(),'admin_module');
    $dates['from'] = isset($criterias['dates']) && $criterias['dates']['from']['day'] && $criterias['dates']['from']['month'] && $criterias['dates']['from']['year']
      ? strtotime($criterias['dates']['from']['year'].'-'.$criterias['dates']['from']['month'].'-'.$criterias['dates']['from']['day'])
      : strtotime('- 1 weeks 00:00:00' );
    $dates['to']   = isset($criterias['dates']) && $criterias['dates']['to']['day'] && $criterias['dates']['to']['month'] && $criterias['dates']['to']['year']
      ? strtotime($criterias['dates']['to']['year'].'-'.$criterias['dates']['to']['month'].'-'.$criterias['dates']['to']['day'].' 23:59:59')
      : strtotime('+ 3 weeks + 1 day 23:59:59');
    $criterias['dates'] = $dates;
    if ( isset($criterias['users']) && count($criterias['users']) > 0 )
    {
      if ( !$criterias['users'][0] )
        array_shift($criterias['users']);
    }
    
    $q = Doctrine::getTable('Price')->createQuery('p')
      ->select('p.id, pt.id, pt.lang, pt.name AS name, p.value, count(t.id) AS nb')
      ->leftJoin('p.Tickets t')
      ->leftJoin('t.Manifestation m')
      ->leftJoin('m.Event e')
      ->leftJoin('t.Transaction tr')
      ->leftJoin('t.Gauge g')
      ->andWhereIn('g.workspace_id',array_keys($this->getUser()->getWorkspacesCredentials()))
      ->andWhereIn('e.meta_event_id',array_keys($this->getUser()->getMetaEventsCredentials()))
      ->andWhere('t.duplicating IS NULL')
      ->andWhere('t.cancelling IS NULL')
      ->andWhere('t.id NOT IN (SELECT tt.cancelling FROM ticket tt WHERE tt.cancelling IS NOT NULL)')
      ->andWhere('m.happens_at > ?',date('Y-m-d H:i:s',$dates['from']))
      ->andWhere('m.happens_at <= ?',date('Y-m-d H:i:s',$dates['to']))
      ->groupBy('p.id, pt.id, pt.lang, pt.name, p.value')
      ->orderBy('pt.name, p.value');
    
    if ( isset($criterias['manifestations_list']) && count($criterias['manifestations_list']) > 0 )
      $q->andWhereIn('t.manifestation_id',$criterias['manifestations_list']);
    if ( isset($criterias['events_list']) && count($criterias['events_list']) > 0 )
      $q->andWhereIn('m.event_id',$criterias['events_list']);
    if ( isset($criterias['users']) && count($criterias['users']) > 0 )
      $q->andWhereIn('t.sf_guard_user_id',$criterias['users']);
    if ( isset($criterias['workspaces_list']) && count($criterias['workspaces_list']) > 0 )
      $q->andWhereIn('g.workspace_id',$criterias['workspaces_list']);
    if ( isset($criterias['meta_events_list']) && count($criterias['meta_events_list']) > 0 )
      $q->andWhereIn('e.meta_event_id',$criterias['meta_events_list']);
    if ( isset($criterias['week_day']) && count($criterias['week_day']) > 0 )
      $q->andWhereIn('extract(dow FROM m.happens_at)', $criterias['week_day']);
      
    if ( !$all )
    {
      $q->andWhere($asked || $ordered ? 'NOT (t.printed_at IS NOT NULL OR t.integrated_at IS NOT NULL)' : '(t.printed_at IS NOT NULL OR t.integrated_at IS NOT NULL)');
      if ( $ordered)
        $q->andWhere('t.transaction_id IN (SELECT oo.transaction_id FROM Order oo)');
      if ( $asked )
        $q->andWhere('t.transaction_id NOT IN (SELECT oo.transaction_id FROM Order oo)');
    }
    elseif ( !sfConfig::get('project_count_demands',false) )
      $q->andWhere('t.printed_at IS NOT NULL OR t.integrated_at IS NOT NULL OR t.transaction_id IN (SELECT oo.transaction_id FROM Order oo)');
    
    return $type == 'array' ? $q->fetchArray() : $q->execute();
  }
}

<?php

/**
 * activity actions.
 *
 * @package    e-venement
 * @subpackage activity
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class activityActions extends sfActions
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
      ->addEventCriterias()
      ->addIntervalCriteria()
    ;
    
    if ( is_array($this->getUser()->getAttribute('stats.criterias',array(),'admin_module')) )
      $this->form->bind($this->getUser()->getAttribute('stats.criterias',array(),'admin_module'));
  }

  public function executeJson(sfWebRequest $request)
  {
    $this->denomination = $request->getParameter('denomination', 'date');
    $this->getResponse()->setContentType('application/json');
    $this->lines = $this->getData($this->denomination);
  }
  
  protected function getData($denomination = 'date')
  {
    $criterias = $this->getUser()->getAttribute('stats.criterias',array(),'admin_module');
    $dates['from'] = isset($criterias['dates']) && $criterias['dates']['from']['day'] && $criterias['dates']['from']['month'] && $criterias['dates']['from']['year']
      ? strtotime($criterias['dates']['from']['year'].'-'.$criterias['dates']['from']['month'].'-'.$criterias['dates']['from']['day'])
      : strtotime('- 3 weeks');
    $dates['to']   = isset($criterias['dates']) && $criterias['dates']['to']['day'] && $criterias['dates']['to']['month'] && $criterias['dates']['to']['year']
      ? strtotime($day = $criterias['dates']['to']['year'].'-'.$criterias['dates']['to']['month'].'-'.$criterias['dates']['to']['day'].' 23:59:59')
      : strtotime('+ 1 weeks + 1 day');
    
    switch ( $denomination ) {
    case 'hour':
      $q = "SELECT COUNT(DISTINCT tck.id) AS nb, date_part('hour', CASE WHEN tck.printed_at IS NOT NULL THEN tck.printed_at ELSE CASE WHEN tck.integrated_at IS NOT NULL THEN tck.integrated_at ELSE o.created_at END END) AS hour
            FROM ticket tck
            LEFT JOIN order_table o ON o.transaction_id = tck.transaction_id
            LEFT JOIN manifestation m ON m.id = tck.manifestation_id";
      
      $subfrom = $subwhere = '';
      if ( isset($criterias['meta_events_list']) && $criterias['meta_events_list'] )
      {
        $subfrom = ' LEFT JOIN event e ON e.id = m.event_id';
        $subwhere = ' AND e.meta_event_id IN ('.implode(',', $criterias['meta_events_list']).')';
      }
      
      $q .= $subfrom;
      $q .= " WHERE (tck.printed_at IS NOT NULL OR tck.integrated_at IS NOT NULL OR o.created_at IS NOT NULL)";
      $q .= $subwhere;
      
      if ( $dates['from'] )
        $q .= " AND m.happens_at >= '".date('Y-m-d', $dates['from'])."' ";
      if ( $dates['to'] )
        $q .= " AND m.happens_at < '".date('Y-m-d', $dates['to'])."' ";
      
      
      $q .= " GROUP BY date_part('hour', CASE WHEN tck.printed_at IS NOT NULL THEN tck.printed_at ELSE CASE WHEN tck.integrated_at IS NOT NULL THEN tck.integrated_at ELSE o.created_at END END)
              ORDER BY date_part('hour', CASE WHEN tck.printed_at IS NOT NULL THEN tck.printed_at ELSE CASE WHEN tck.integrated_at IS NOT NULL THEN tck.integrated_at ELSE o.created_at END END)";
      break;

    default:
      $interval = isset($criterias['interval']) && intval($criterias['interval']) > 0
        ? intval($criterias['interval'])
        : 1;
      
      $subfrom = '';
      $subwhere = '';
      if ( isset($criterias['meta_events_list']) && $criterias['meta_events_list'] )
      {
        $subfrom .= 'LEFT JOIN manifestation m%%cpt%% ON m%%cpt%%.id = tck%%cpt%%.manifestation_id LEFT JOIN event e%%cpt%% ON e%%cpt%%.id = m%%cpt%%.event_id';
        $subwhere .= 'e%%cpt%%.meta_event_id IN ('.implode(',', $criterias['meta_events_list']).') AND ';
      }
      
      for ( $days = array($dates['from']) ; $days[count($days)-1]+86400*$interval < $dates['to'] ; $days[] = $days[count($days)-1]+86400*$interval );
      foreach ( $days as $key => $day )
        $days[$key] = date('Y-m-d',$day);
      
      $q = "SELECT d.date, d.date + '$interval days'::interval AS end,
            (SELECT count(DISTINCT tck1.id) FROM ticket tck1 ".str_replace('%%cpt%%', '1', $subfrom)." WHERE ".str_replace('%%cpt%%', '1', $subwhere)." (tck1.printed_at IS NOT NULL AND tck1.printed_at >= d.date::date AND tck1.printed_at < d.date + '$interval days'::interval OR tck1.integrated_at IS NOT NULL AND tck1.integrated_at >= d.date::date AND tck1.integrated_at < d.date + '$interval days'::interval) AND tck1.duplicating IS NULL AND tck1.cancelling IS NULL AND tck1.id NOT IN (SELECT cancelling FROM ticket WHERE cancelling IS NOT NULL)) AS printed,
            (SELECT count(DISTINCT tck2.id) FROM ticket tck2 ".str_replace('%%cpt%%', '2', $subfrom)." WHERE ".str_replace('%%cpt%%', '2', $subwhere)." tck2.printed_at IS NULL AND tck2.integrated_at IS NULL AND tck2.transaction_id IN (SELECT transaction_id FROM order_table WHERE updated_at >= d.date::date AND updated_at < d.date + '$interval days'::interval) AND tck2.duplicating IS NULL AND tck2.cancelling IS NULL AND tck2.id NOT IN (SELECT cancelling FROM ticket WHERE cancelling IS NOT NULL)) AS ordered,
            (SELECT count(DISTINCT tck3.id) FROM ticket tck3 ".str_replace('%%cpt%%', '3', $subfrom)." WHERE ".str_replace('%%cpt%%', '3', $subwhere)." tck3.created_at >= d.date::date AND tck3.created_at < d.date + '$interval days'::interval AND tck3.printed_at IS NULL AND tck3.integrated_at IS NULL AND tck3.transaction_id NOT IN (SELECT transaction_id FROM order_table) AND tck3.duplicating IS NULL AND tck3.cancelling IS NULL AND tck3.id NOT IN (SELECT cancelling FROM ticket WHERE cancelling IS NOT NULL)) AS asked,
            (SELECT count(DISTINCT tck4.id) FROM ticket tck4 ".str_replace('%%cpt%%', '4', $subfrom)." LEFT JOIN manifestation m ON m.id = tck4.manifestation_id WHERE ".str_replace('%%cpt%%', '4', $subwhere)." m.happens_at >= d.date::date AND m.happens_at < d.date + '$interval days'::interval AND (printed_at IS NOT NULL OR integrated_at IS NOT NULL) AND tck4.duplicating IS NULL AND cancelling IS NULL AND tck4.id NOT IN (SELECT cancelling FROM ticket WHERE cancelling IS NOT NULL)) AS passing
          FROM (SELECT '".implode("'::date AS date UNION SELECT '",$days)."'::date AS date) AS d
          ORDER BY date";
      break;
    }
    
    $pdo = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
    $stmt = $pdo->prepare($q);
    $stmt->execute();
    
    return $stmt->fetchAll();
  }
}

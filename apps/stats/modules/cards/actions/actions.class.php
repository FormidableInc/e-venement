<?php

/**
 * cards actions.
 *
 * @package    e-venement
 * @subpackage cards
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class cardsActions extends sfActions
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
    
    if ( $request->hasParameter('accounting') )
    {
      $this->accounting = $request->getParameter('accounting');
      $this->getUser()->setAttribute('stats.accounting',$this->accounting,'admin_module');
      $this->redirect($this->getContext()->getModuleName().'/index');
    }
    
    $this->form = new StatsCriteriasForm;
    if ( is_array($this->getUser()->getAttribute('stats.criterias',array(),'admin_module')) )
      $this->form->bind($this->getUser()->getAttribute('stats.criterias',array(),'admin_module'));
    
    if ( is_array($this->getUser()->getAttribute('stats.accounting',array(),'admin_module')) )
      $this->accounting = $this->getUser()->getAttribute('stats.accounting',array('vat' => 0, 'price' => array()),'admin_module');
    
    $this->dates = $this->getDatesCriteria();
    $this->cards = $this->getMembersCards($this->dates['from'],$this->dates['to']);
  }
  
  public function executeJson(sfWebRequest $request)
  {
    $this->getResponse()->setContentType('application/json');
    $dates = $this->getDatesCriteria();
    $this->lines = $this->getMembersCards($dates['from'],$dates['to']);
  }
  
  protected function getMembersCards( $from = NULL, $until = NULL )
  {
    // default values
    if ( is_null($from) )
      $from = date('Y-m-d');
    if ( is_null($until) )
      $until = date('Y-m-d',strtotime('1 year'));
    
    // SQL query
    $q = "SELECT mct.name, count(*) AS nb
          FROM member_card mc
          INNER JOIN member_card_type mct ON mct.id = mc.member_card_type_id
          WHERE mc.created_at BETWEEN :from AND :until
          AND mc.contact_id IS NOT NULL
          GROUP BY mct.name
          ORDER BY mct.name
        ";
    
    $pdo = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
    $stmt = $pdo->prepare($q);
    $stmt->execute(array('from' => $from, 'until' => $until));
    
    return $stmt->fetchAll();
  }
  
  public function getDatesCriteria()
  {
    $this->criterias = $this->getUser()->getAttribute('stats.criterias',array(),'admin_module');
    
    // dates
    $dates = isset($this->criterias['dates']) ? $this->criterias['dates'] : array();
    if ( isset($dates['from'])
      && isset($dates['from']['day']) && isset($dates['from']['month']) && isset($dates['from']['year'])
      && $dates['from']['day'] && $dates['from']['month'] && $dates['from']['year'] )
      $dates['from'] = $dates['from']['year'].'-'.$dates['from']['month'].'-'.$dates['from']['day'];
    else
      $dates['from'] = date('Y-m-d',strtotime(sfConfig::get('app_cards_expiration_delay').' ago'));
    
    if ( isset($dates['to'])
      && isset($dates['to']['day']) && isset($dates['to']['month']) && isset($dates['to']['year'])
      && $dates['to']['day'] && $dates['to']['month'] && $dates['to']['year'] )
      $dates['to'] = $dates['to']['year'].'-'.$dates['to']['month'].'-'.$dates['to']['day'];
    else
      $dates['to'] = date('Y-m-d');
    
    return $dates;
  }
}

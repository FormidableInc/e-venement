<?php

require_once dirname(__FILE__).'/../lib/summaryGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/summaryGeneratorHelper.class.php';

/**
 * summary actions.
 *
 * @package    e-venement
 * @subpackage summary
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class summaryActions extends autoSummaryActions
{
  protected $type = 'debts';
  
  public function executeFilter(sfWebRequest $request)
  {
    $this->type = $request->getParameter('type');
    
    $this->hasFilters = $this->getUser()->getAttribute('contact.filters', $this->configuration->getFilterDefaults(), 'admin_module');
    $this->setPage(1);

    if ($request->hasParameter('_reset'))
    {
      $this->setFilters($this->configuration->getFilterDefaults());
      $this->redirect($this->type ? 'summary/'.$this->type : '@transaction');
    }

    $this->filters = $this->configuration->getFilterForm($this->getFilters());

    $this->filters->bind($request->getParameter($this->filters->getName()));
    if ($this->filters->isValid())
    {
      $this->setFilters($this->filters->getValues());
      $this->redirect($this->type ? 'summary/'.$this->type : '@transaction');
    }

    $this->pager = $this->getPager();
    $this->sort = $this->getSort();

    $this->setTemplate('index');

    parent::executeFilter($request);
  }
  public function executeIndex(sfWebRequest $request)
  {
    $this->type = $this->type ? $this->type : 'debts';
    parent::executeIndex($request);
  }
  public function executeDuplicatas(sfWebRequest $request)
  {
    $this->type = 'duplicatas';
    $this->executeIndex($request);
    $this->setTemplate('index');
  }
  
  public function executeDebts(sfWebRequest $request)
  {
    if ( $request->hasParameter('all') )
      $this->type = 'all';
    $this->executeIndex($request);
    $this->setTemplate('index');
  }
  public function executeAsks(sfWebRequest $request)
  {
    $this->type = 'asks';
    $this->class = 'asks';
    $this->executeIndex($request);
    $this->setTemplate('index');
  }
  public function executeDeleteDemands(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    
    Doctrine::getTable('Ticket')->createQuery('tck')
      ->delete()
      ->andWhere('tck.transaction_id = ?',$request->getParameter('id'))
      ->andWhere('tck.printed = false AND tck.integrated = false')
      ->andWhere('tck.transaction_id NOT IN (SELECT o.transaction_id FROM Order o)')
      ->execute();
      
    $this->getUser()->setFlash('notice',__('Demands deleted properly'));
    $this->redirect('summary/asks');
  }
  
  public function buildQuery()
  {
    $q = parent::buildQuery();
    $t = $q->getRootAlias();
    
    $q->andWhere('tck.id IS NOT NULL')
      ->leftJoin("$t.User u")
      ->leftJoin("$t.Payments pay")
      ->orderBy("$t.id DESC");
    
    if ( !$q->contains("LEFT JOIN $t.Professional p") )
      $q->leftJoin("$t.Professional p");
    if ( !$q->contains("LEFT JOIN p.Organism o") )
      $q->leftJoin("p.Organism o");
    if ( !$q->contains("LEFT JOIN $t.Contact c") )
      $q->leftJoin("$t.Contact c");

    switch ( $this->type ) {
    case 'asks':
      $q->andWhere('tck.printed = FALSE')
        ->andWhere('tck.duplicate IS NULL');
      break;
    case 'duplicatas':
      $q->andWhere('tck.duplicate IS NOT NULL')
        ->andWhere('tck.printed = TRUE');
      break;
    case 'debts':
      // debts
      $rq = new Doctrine_RawSql();
      $rq->select('t.id')
        ->from('Transaction t')
        ->addComponent('t','Transaction')
        //->andWhere("(SELECT CASE WHEN SUM(tt.value) IS NULL THEN 0 ELSE SUM(tt.value) END FROM Ticket tt WHERE transaction_id = t.id AND (tt.printed OR tt.cancelling IS NOT NULL) AND tt.duplicate IS NULL) != (CASE WHEN (SELECT SUM(pp.value) FROM Payment pp WHERE pp.transaction_id = t.id) IS NULL THEN 0 ELSE (SELECT SUM(pp.value) FROM Payment pp WHERE pp.transaction_id = t.id) END)");
        ->andWhere("(SELECT CASE WHEN SUM(tt.value) IS NULL THEN 0 ELSE SUM(tt.value) END FROM Ticket tt WHERE transaction_id = t.id AND (tt.printed OR tt.cancelling IS NOT NULL) AND tt.duplicate IS NULL) != (SELECT CASE WHEN SUM(pp.value) IS NULL THEN 0 ELSE SUM(pp.value) END FROM Payment pp WHERE pp.transaction_id = t.id)");
      $ids = $rq->execute(array(),Doctrine::HYDRATE_NONE);
      foreach ( $ids as $key => $id )
        $ids[$key] = $id[0];
      $q->andWhereIn("$t.id",$ids);
    default:
      // all transactions
      break;
    }
    
    return $q;
  }
}

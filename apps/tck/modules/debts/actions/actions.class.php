<?php

require_once dirname(__FILE__).'/../lib/debtsGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/debtsGeneratorHelper.class.php';

/**
 * debts actions.
 *
 * @package    symfony
 * @subpackage debts
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class debtsActions extends autoDebtsActions
{
  public function executeShow(sfWebRequest $request)
  {
    parent::executeShow($request);
    $this->redirect('ticket/sell?id='.$this->transaction->id);
  }

  protected function getPager()
  {
    $pager = $this->configuration->getPager('Transaction');

    if (is_null($this->filters))
    {
      $this->filters = $this->configuration->getFilterForm($this->getFilters());
    }

    $filters = $this->getFilters();

    $pager->setTableMethod('findDebts');
    $pager->setTableCountMethod('findDebtsCount');

    $pager->setFilters($filters);
    $pager->setPage($this->getPage());
    $pager->init();

    return $pager;
  }

}

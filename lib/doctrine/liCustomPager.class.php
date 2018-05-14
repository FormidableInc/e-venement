<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) Jonathan H. Wage <jonwage@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfDoctrine pager class.
 *
 * @package    sfDoctrinePlugin
 * @subpackage pager
 * @author     Jonathan H. Wage <jonwage@gmail.com>
 * @version    SVN: $Id: sfDoctrinePager.class.php 28897 2010-03-30 20:30:24Z Jonathan.Wage $
 */
class liCustomPager extends sfDoctrinePager implements Serializable
{
  protected
    $tableCountMethodName = null,
    $offset = 0,
    $limit = 0,
    $filters = [];

  public function getTableCountMethod()
  {
    return $this->tableCountMethodName;
  }

  public function setTableCountMethod($tableCountMethodName)
  {
    $this->tableCountMethodName = $tableCountMethodName;
  }

  public function getCount()
  {
    $method = $this->getTableCountMethod();

    $count = Doctrine_Core::getTable($this->getClass())->$method($this->filters);

    return $count;
  }

  public function setFilters($filters)
  {
    $this->filters = $filters;
  }

  /**
   * @see sfPager
   */
  public function init()
  {
    $this->resetIterator();

    $count = $this->getCount();

    $this->setNbResults($count);

    if (0 == $this->getPage() || 0 == $this->getMaxPerPage() || 0 == $this->getNbResults())
    {
      $this->setLastPage(0);
    }
    else
    {
      $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

      $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));

      $this->offset = $offset;
      $this->limit = $this->getMaxPerPage();
    }
  }

  /**
   * Get all the results for the pager instance
   *
   * @param mixed $hydrationMode A hydration mode identifier
   *
   * @return Doctrine_Collection|array
   */
  public function getResults($hydrationMode = null)
  {
    $method = $this->getTableMethod();
    $results = Doctrine_Core::getTable($this->getClass())->$method($this->offset, $this->limit, $this->filters);

    return $results;
  }

}

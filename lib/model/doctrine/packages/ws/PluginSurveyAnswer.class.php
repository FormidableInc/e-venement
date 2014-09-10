<?php

/**
 * PluginSurveyAnswer
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class PluginSurveyAnswer extends BaseSurveyAnswer
{
  public function save(Doctrine_Connection $conn = null)
  {
    // to avoid saving empty answers
    if ( !trim($this->value) )
    {
      $this->delete();
      return;
    }
    parent::save($conn);
  }
  public function getIndexesPrefix()
  {
    return strtolower(get_class($this));
  }
}
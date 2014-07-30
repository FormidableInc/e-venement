<?php

/**
 * PluginSurveyQueryOptionTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class PluginSurveyQueryOptionTable extends Doctrine_Table implements CompositeSearchableTable
{
  public function batchUpdateIndex($limit = null, $offset = null, $encoding = null)
  {
    if ( !$this->hasTemplate('Searchable') )
      return false;
    
    return $this->getTemplate('Searchable')->getListener()->get('Searchable')->batchUpdateIndex($limit, $offset, $encoding);
  }
    /**
     * Returns an instance of this class.
     *
     * @return object PluginSurveyQueryOptionTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('PluginSurveyQueryOption');
    }
}

<?php

/**
 * PluginTaxTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class PluginTaxTable extends TraceableTable
{
    /**
     * Returns an instance of this class.
     *
     * @return object PluginTaxTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('PluginTax');
    }

  // Optimization for domain restrictions
  public function getRelation($alias, $recursive = true)
  {
    $rel = parent::getRelation($alias, $recursive);
    try { $rel = liDoctrineRelationAssociationUsers::create($rel); }
    catch ( liEvenementException $e ) { }
    return $rel;
  }
}

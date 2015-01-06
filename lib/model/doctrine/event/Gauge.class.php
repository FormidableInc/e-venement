<?php

/**
 * Gauge
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Gauge extends PluginGauge
{
  public function __toString()
  {
    return (string)$this->Workspace->name;
  }
  
  public function getFree($count_demands = false)
  {
    return $this->value
      - $this->printed
      - $this->ordered
      - ($count_demands ? $this->asked : 0);
  }
  
  public function preSave($event)
  {
    if ( is_null($this->value) )
      $this->value = 0;
    parent::preSave($event);
  }
  
  public function getSeatedPlan()
  {
    return $this->Manifestation->Location->getWorkspaceSeatedPlan($this->workspace_id);
  }
  
  public function getPriceMax($users = NULL)
  {
    $values = $this->getAllPriceValues($users);
    if ( count($values) == 0 )
      return 0;
    return max($values);
  }
  public function getPriceMin($users = NULL)
  {
    $values = $this->getAllPriceValues($users);
    if ( count($values) == 0 )
      return 0;
    
    return min($values);
  }
  public function getAllPriceValues($users = NULL)
  {
    $prices = array();
    foreach ( $this->PriceGauges as $pg )
    {
      $go = !(is_array($users) && count($users) > 0);
      if ( !$go )
      foreach ( $users as $user )
      if ( $user instanceof liGuardSecurityUser && $pg->Price->isAccessibleBy($user)
        || is_integer($user) && in_array($user, $pg->Price->Users->getPrimaryKeys()) )
      {
        $go = true;
        break;
      }
      
      if ( $go )
        $prices[$pg->price_id] = $pg->value;
    }
    
    foreach ( $this->Manifestation->PriceManifestations as $pm )
    if ( !isset($prices[$pm->price_id]) )
    {
      $go = !(is_array($users) && count($users) > 0);
      if ( !$go )
      foreach ( $users as $user )
      if ( $user instanceof liGuardSecurityUser && $pm->Price->isAccessibleBy($user)
        || is_integer($user) && in_array($user, $pm->Price->Users->getPrimaryKeys()) )
      {
        $go = true;
        break;
      }
      
      if ( $go )
        $prices[$pm->price_id] = $pm->value;
    }
    return $prices;
  }
}

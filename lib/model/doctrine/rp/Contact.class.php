<?php

/**
 * Contact
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Contact extends PluginContact
{
  protected $module = 'contact';
  
  public function __toString()
  {
    return strtoupper($this->name).' '.$this->firstname;
  }
  
  public function getYOBsString()
  {
    $arr = array();
    foreach ( $this->YOBs as $YOB )
      $arr[] = (string)$YOB;
    sort($arr);
    return implode(', ',$arr);
  }
  
  public function getIdBarcoded()
  {
    $c = ''.$this->id;
    $n = strlen($c);
    for ( $i = 12-$n ; $i > 0 ; $i-- )
      $c = '0'.$c;
    return $c;
  }
}

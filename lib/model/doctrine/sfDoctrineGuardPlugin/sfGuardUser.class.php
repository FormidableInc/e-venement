<?php

/**
 * sfGuardUser
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class sfGuardUser extends PluginsfGuardUser
{
  public function save(Doctrine_Connection $conn = null)
  {
    if ( $this->email_address && $this->username )
      parent::save($conn);
  }
  
  public function preSave($event)
  {
    parent::preSave($event);
    $this->clearCache($event);
  }
  
  public function postInsert($event)
  {
    if ( sfContext::hasInstance() && in_array('liOnlineSalesPlugin', sfContext::getInstance()->getConfiguration()->getPlugins()) )
    {
      $osApp = new OsApplication();
      $osApp->User = $this;
      $osApp->save();
    }
  }
  
  public function postUpdate($event)
  {
    parent::postUpdate($event);
    
    if ( sfContext::hasInstance() && in_array('liOnlineSalesPlugin', sfContext::getInstance()->getConfiguration()->getPlugins()) )
    {
      $osApp = Doctrine::getTable('OsApplication')->findOneByUserId($this->id);
      
      if ( $osApp )
      {
        if ( $osApp->secret != $this->password )
        {
          $osApp->secret = $this->password;
          $osApp->save();
        }
        if ( $osApp->identifier != $this->username )
        {
          $osApp->identifier = $this->username;
          $osApp->save();
        }
      }
    }
  }
  
  public function preDelete($event)
  {
    parent::preDelete($event);
    $this->clearCache($event);
  }
  
  public function clearCache($event = NULL)
  {
    // clear cache
    Doctrine::getTable('Cache')->createQuery('c')
      ->andWhere('c.domain = ?', 'rp-index')
      ->andWhere('c.identifier LIKE ?', '%sf_guard_user_id='.$this->id)
      ->delete()
      ->execute();
  }
  
  public function __toString()
  {
    return (string) $this->getUsername().' ('.$this->getName().')';
  }
}

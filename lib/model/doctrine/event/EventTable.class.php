<?php

/**
 * EventTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class EventTable extends PluginEventTable
{
    /**
     * Returns an instance of this class.
     *
     * @return object EventTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Event');
    }

  public function createQuery($alias = 'a')
  {
    $me  = 'me'   != $alias ? 'me'   : 'me1';
    $ec  = 'ec'   != $alias ? 'ec'   : 'ec1';
    $m   = 'm'    != $alias ? 'm'    : 'm1';
    
    return parent::createQuery($alias)
      ->leftJoin("$alias.MetaEvent $me")
      ->leftJoin("$alias.EventCategory $ec")
    ;
  }
  
  public function getOnlyGroupEvents()
  {
    return $this->createQuery('e')
      ->leftJoin('e.Manifestations m')
      ->leftJoin('m.Gauges g')
      ->leftJoin('g.Workspace w')
      ->leftJoin('w.GroupWorkspace gw')
      ->andWhere('gw.id IS NOT NULL');
  }
  
  public function retrieveList()
  {
    $cid = 0;
    if ( sfContext::hasInstance() && method_exists(sfContext::getInstance()->getUser(), 'getContactId') )
      $cid = sfContext::getInstance()->getUser()->getContactId();
    
    return $this->createQuery('e')
      ->select('e.*, ec.*, me.*, m.*, l.*, c.*, g.*')
      ->addSelect('(SELECT max(mm2.happens_at) AS max_date FROM Manifestation mm2 WHERE mm2.event_id = e.id) AS max_date')
      ->leftJoin('e.Manifestations m ON m.event_id = e.id AND (m.reservation_confirmed = TRUE OR m.contact_id = '.$cid.')')
      ->leftJoin('m.Color c')
      ->leftJoin('m.Gauges g')
      ->leftJoin('m.Location l');
  }
  public function retrievePublicList()
  {
    return $this->retrieveList()
      ->andWhere('g.online = TRUE')
      ->andWhere('m.happens_at > NOW()');
  }
}

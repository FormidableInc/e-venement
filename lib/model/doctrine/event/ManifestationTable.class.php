<?php
/**********************************************************************************
*
*	    This file is part of e-venement.
*
*    e-venement is free software; you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation; either version 2 of the License.
*
*    e-venement is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with e-venement; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
*    Copyright (c) 2006-2013 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2006-2013 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php

/**
 * ManifestationTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class ManifestationTable extends PluginManifestationTable
{
  public static function getInstance()
  {
      return Doctrine_Core::getTable('Manifestation');
  }
  
  public function retrieveList()
  {
    return $this->createQuery()
      ->removeDqlQueryPart('orderby');
  }
  public function createQuery($alias = 'm', $light = false)
  {
    $e  = $alias != 'e'  ? 'e'  : 'e1';
    $me = $alias != 'me' ? 'me' : 'me1';
    $l  = $alias != 'l'  ? 'l'  : 'l1';
    $pm = $alias != 'pm' ? 'pm' : 'pm1';
    $p  = $alias != 'p'  ? 'p'  : 'p1';
    $g  = $alias != 'g'  ? 'g'  : 'g1';
    $t  = $alias != 't'  ? 't'  : 't1';
    $o  = $alias != 'o'  ? 'o'  : 'o1';
    $c  = $alias != 'c'  ? 'c'  : 'c1';
    $w  = $alias != 'w'  ? 'w'  : 'w1';
    $wuo = $alias != 'wuo' ? 'wuo' : 'wuo1';
    $tck = $alias != 'tck' ? 'tck' : 'tck1';
    $tr = $alias != 'tr'  ? 'tr' : 'tr1';
    $wu = $alias != 'wu'  ? 'wu' : 'wu1';
    $wwp = $alias != 'wwp'  ? 'wwp' : 'wwp1';
    $pwp = $alias != 'pwp'  ? 'pwp' : 'pwp1';
    $pu = $alias != 'pu'  ? 'pu' : 'pu1';
    $meu = $alias != 'meu' ? 'meu' : 'meu1';
    
    $q = parent::createQuery($alias)
      ->leftJoin("$alias.Event $e")
      ->leftJoin("$e.MetaEvent $me")
      ->leftJoin("$alias.Location $l");
    
    // security features: limitating manifestation's access to owner, or confirmed manifestation, or confirmations administrator
    $uid = 0;
    if ( sfContext::hasInstance() )
    {
      $credentials = Manifestation::getCredentials();
      
      $q->andWhere("($alias.reservation_confirmed = ? OR ? OR ($alias.contact_id IS NOT NULL AND $alias.contact_id = ?) OR ?)", array(
        true, // confirmed
        sfContext::getInstance()->getUser()->hasCredential($credentials['reservation_confirmed']), // can access to all manifs
        $cid = in_array(sfContext::getInstance()->getConfiguration()->getApplication(), array('pub','ws')) ? 0 : sfContext::getInstance()->getUser()->getContactId(), // the manif has got a contact_id and it's yours
        sfContext::getInstance()->getUser()->hasCredential($credentials['contact_id']), // you can modify the manif's contact
      ));
    }
    
    if ( !$light )
    {
      $q->leftJoin("$alias.PriceManifestations $pm")
        ->leftJoin("$pm.Price $p")
        ->leftJoin("$alias.Gauges $g")
        ->leftJoin("$g.Workspace $w")
        ->leftJoin("$alias.Organizers $o")
        ->orderBy("$e.name, $me.name, $alias.happens_at, $alias.duration, $w.name");
      if ( sfContext::hasInstance() )
      $q->leftJoin("$w.Order $wuo ON $wuo.workspace_id = $w.id AND $wuo.sf_guard_user_id = ".($uid = sfContext::getInstance()->getUser()->getId() ))
        ->orderBy("$e.name, $me.name, $alias.happens_at, $alias.duration, $wuo.rank")
        ->leftJoin("$w.Users $wu")
        ->leftJoin("$me.Users $meu")
        ->andWhere("$meu.id = ?",$uid)
        ->andWhere("$wu.id = ? OR $wu.id IS NULL",$uid)
        ->leftJoin("$p.UserPrices $pu ON $pu.price_id = $p.id AND $pu.sf_guard_user_id = ".$uid)
        //->leftJoin("$w.WorkspacePrices $wwp ON $wwp.workspace_id = $w.id AND $wwp.price_id = $p.id")
        //->leftJoin("$p.WorkspacePrices $pwp ON $pwp.workspace_id = $w.id AND $pwp.price_id = $p.id")
        //->andWhere("$meu.id = ? AND ($wu.id = ? OR $wu.id IS NULL) AND ($pu.id = ? OR $pu.id IS NULL)",array($uid,$uid,$uid))
      ;
      
      //if ( sfContext::hasInstance() && $uid = sfContext::getInstance()->getUser()->getId() )
      //  $q->andWhere("$pm.id IS NULL OR $pm.price_id IN (SELECT price_id FROM UserPrice up WHERE up.user_id = ?)",$uid);
    }
    
    return $q;
  }

  public function createQueryByEventId($id)
  {
    $q = $this->createQuery('m',true);
    $a = $q->getRootAlias();
    $q
      ->leftJoin("$a.Gauges g")
      ->leftJoin("g.Workspace w")
      ->leftJoin("w.Order wuo ON wuo.workspace_id = w.id AND wuo.sf_guard_user_id = ".($uid = sfContext::getInstance()->getUser()->getId() ))
      ->andWhere('e.id = ?',$id)
      ->orderby("e.name, $a.happens_at DESC, l.name");
    return $q;
  }
  public function createQueryByLocationId($id)
  {
    $q = $this->createQuery();
    $a = $q->getRootAlias();
    $q
      ->andWhere('l.id = ?',$id)
      ->orderby("e.name, $a.happens_at DESC, l.name");
    return $q;
  }
  
  public function fetchOneByGaugeId($id)
  {
    return $this->createQuery('m')->andWhere('g.id = ?',$id)->fetchOne();
  }
  
  public function retrieveConflicts()
  {
    $conflicts = $this->getConflicts();
    $conflicts[-1] = 0;
    $q = $this->createQuery('m')
      ->andWhere('m.reservation_confirmed = TRUE')
      ->andWhere('m.blocking = TRUE')
      ->andWhereIn('m.id',array_keys($conflicts))
      ->andWhere('m.reservation_ends_at > now()')
      ->removeDqlQueryPart('orderby')
    ;
    
    if ( sfContext::hasInstance() && !sfContext::getInstance()->getUser()->hasCredential('event-access-all') )
      $q->andWhere('m.contact_id = ?',sfContext::getInstance()->getUser()->getContactId());
    
    return $q;
  }
  
  public function retrievePending()
  {
    $q = $this->createQuery('m')
      ->andWhere('m.reservation_confirmed = FALSE')
      ->removeDqlQueryPart('orderby')
    ;
    
    if ( sfContext::hasInstance() && !sfContext::getInstance()->getUser()->hasCredential('event-access-all') )
      $q->andWhere('m.contact_id = ?',sfContext::getInstance()->getUser()->getContactId());
    
    return $q;
  }
  
  /**
    * Method which returns an array of conflicts, depending on filters
    * Filters are used with values :
    * - id, for the manifestation's id to focus on
    * - potentially:
    *   - TRUE if all manifestations have to be checked, confirmed or not
    *   - can be focused on a particular manifestation's id, to check the potential conflicts which will happen if it would be confirmed
    *
    **/
  public function getConflicts(array $filters = array())
  {
    // preconditions
    if ( isset($filters['id']) && intval($filters['id']).'' !== ''.$filters['id'] )
      throw new sfInitializationException('Bad value given for "ID" (INT expected): ('.gettype($filters['id']).') '.$filters['id']);
    if ( isset($filters['potentially']) && $filters['potentially'] !== true && intval($filters['potentially']).'' !== ''.$filters['potentially'] )
      throw new sfInitializationException('Bad value given for "potentially" (TRUE or INT expected): ('.gettype($filters['potentially']).') '.$filters['potentially']);
    
    // the root raw query
    $m2_start = "CASE WHEN m2.happens_at < m2.reservation_begins_at THEN m2.happens_at ELSE m2.reservation_begins_at END";
    $m_start  = "CASE WHEN m1.happens_at < m1.reservation_begins_at THEN m1.happens_at ELSE m1.reservation_begins_at END";
    $m2_stop  = "CASE WHEN m2.happens_at + (m2.duration||' seconds')::interval > m2.reservation_ends_at THEN m2.happens_at + (m2.duration||' seconds')::interval ELSE m2.reservation_ends_at END";
    $m_stop   = "CASE WHEN m1.happens_at + (m1.duration||' seconds')::interval > m1.reservation_ends_at THEN m1.happens_at + (m1.duration||' seconds')::interval ELSE m1.reservation_ends_at END";
    $q = "SELECT m1.id, m2.id AS conflicted_id,
                 CASE WHEN l1.id           = l2.id           THEN l1.id           ELSE
                 CASE WHEN lb1.location_id = lb2.location_id THEN lb1.location_id ELSE
                 CASE WHEN l1.id           = lb2.location_id THEN l1.id           ELSE
                 CASE WHEN l2.id           = lb1.location_id THEN l2.id           ELSE
                 NULL END END END END AS resource_id
          FROM manifestation m1
          LEFT JOIN manifestation m2
            ON ( $m2_start >= $m_start AND $m2_start < $m_stop
              OR $m2_stop  >= $m_start AND $m2_stop  < $m_stop
              OR $m2_start <= $m_start AND $m2_stop >= $m_stop )
            AND m2.id != m1.id
          LEFT JOIN location l1 ON l1.id = m1.location_id
          LEFT JOIN location l2 ON l2.id = m2.location_id
          LEFT JOIN location_booking lb1 ON lb1.manifestation_id = m1.id
          LEFT JOIN location_booking lb2 ON lb2.manifestation_id = m2.id
          WHERE m2.blocking AND m1.blocking AND ".( isset($filters['potentially']) && $filters['potentially'] === true ? 'TRUE = :potentially' : 'm2.reservation_confirmed' ).' AND '.(isset($filters['potentially']) ? ($filters['potentially'] === true ? 'TRUE = :potentially' : '(m1.reservation_confirmed OR m1.id = :potentially)') : 'm1.reservation_confirmed')."
            AND (m1.location_id = m2.location_id OR m1.location_id = lb2.location_id OR m2.location_id = lb1.location_id OR lb1.location_id = lb2.location_id)
            AND m2.id IS NOT NULL
            ".(isset($filters['id']) ? 'AND m1.id = :id' : '')."
          ORDER BY m1.id";
    
    $pdo = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
    $stmt = $pdo->prepare($q);
    $stmt->execute($filters);
    $manifs = $stmt->fetchAll();
    
    $conflicts = array();
    foreach ( $manifs as $manif )
    {
      if ( !isset($conflicts[$manif['id']]) )
        $conflicts[$manif['id']] = array();
      if ( !isset($conflicts[$manif['id']][$manif['resource_id']]) )
        $conflicts[$manif['id']][$manif['resource_id']] = array();
      $conflicts[$manif['id']][$manif['resource_id']][] = $manif['conflicted_id'];
    }
    
    return $conflicts;
  }
  
  public function retrievePublicList()
  {
    return $this->retrieveList()
      ->andWhere('g.online = TRUE');
  }
}

<?php

/**
 * Manifestation
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Manifestation extends PluginManifestation implements liUserAccessInterface
{
  public $current_version = NULL;
  
  public function getName($short = false)
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('I18N','Date'));
    $name = $short && $this->Event->short_name ? $this->Event->short_name : $this->Event;
    return $name.' '.__('at').' '.$this->getShortenedDate();
  }
  public function getHappensAtTimeHR()
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('Date'));
    return format_datetime($this->happens_at, 'HH:mm');
  }
  public function getNameWithFullDate()
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('I18N'));
    return $this->Event->name.' '.__('at').' '.$this->getFormattedDate();
  }
  public function getFormattedDate()
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('Date'));
    return format_datetime($this->happens_at,'EEEE d MMMM yyyy HH:mm');
  }
  public function getShortenedDate()
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('Date'));
    return format_datetime($this->happens_at,'EEE d MMM yyyy HH:mm');
  }
  public function getShortenedEndDate()
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('Date'));
    return format_datetime($this->ends_at,'EEE d MMM yyyy HH:mm');
  }
  public function getMiniDate()
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('Date'));
    return format_datetime($this->happens_at,'dd/MM/yyyy HH:mm');
  }
  public function getMiniEndDate()
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('Date'));
    return format_datetime($this->ends_at,'dd/MM/yyyy HH:mm');
  }
  public function getShortName()
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('I18N','Date'));
    return $this->Event->name.' '.__('at').' '.format_date($this->happens_at);
  }
  public function getEndsAt()
  {
    return date('Y-m-d H:i:s',strtotime($this->happens_at)+$this->duration);
  }
  public function setEndsAt($ends_at)
  {
    $this->duration = strtotime($ends_at) - strtotime($this->happens_at);
    return $this;
  }
  public function getEndsAtTime()
  {
    return strtotime($this->happens_at)+$this->duration;
  }
  public function getHappensAtTime()
  {
    return strtotime($this->happens_at);
  }
  public function getCreatedAtTime()
  {
    return strtotime($this->created_at);
  }
  public function getUpdatedAtTime()
  {
    return strtotime($this->happens_at);
  }
  public function __toString()
  {
    return $this->getName();
  }
  
  /**
    * method getBestFreeSeat()
    * concerning the seated sales
    * find out what is the best seat still free
    *
    * @param  integer       $limit the number of seats to return
    * @return Doctrine_Collection|Seat|false    the best seat(s) still free of any booking, or false if no seat is available
    *
    **/
  public function getBestFreeSeat($limit = 1)
  {
    try {
      if ( $this->getFromCache('best-free-seat-limit') !== $limit )
        $this->clearCache();
      return $this->getFromCache('best-free-seat');
    }
    catch ( liEvenementException $e )
    {
      $this->setInCache('best-free-seat-limit', $limit);
      
      $q = Doctrine::getTable('Seat')->createQuery('s')
        ->leftJoin('s.SeatedPlan sp')
        ->leftJoin('sp.Workspaces ws')
        ->leftJoin('ws.Gauges g')
        ->leftJoin('g.Manifestation m')
        ->andWhere('m.location_id = sp.location_id')
        ->andWhere('m.id = ?', $this->id)
        ->leftJoin('s.Tickets tck WITH tck.manifestation_id = m.id')
        ->andWhere('tck.id IS NULL')
        ->orderBy('s.rank, s.name')
        ->select('s.*')
        ->limit($limit)
      ;
      $this->setInCache('best-free-seat', $limit > 1 ? $q->execute() : $q->fetchOne());
      return $this->getBestFreeSeat($limit);
    }
  }
  
  /**
    * method hasAnyConflict()
    * concerning the resources management
    * Precondition: the values that are used are those which are recorded in DB
    *
    * @return if the object is or would be in conflict with an other one
    *
    **/
  public function hasAnyConflict()
  {
    if ( !$this->blocking )
      return false;
    
    try { $this->getFromCache('has-any-conflict'); }
    catch ( liEvenementException $e )
    {
    
    $rids = array();
    foreach ( $this->Booking as $r )
      $rids[] = $r->id;
    $rids[] = $this->location_id;
    
    $m2_start = "CASE WHEN m.happens_at < m.reservation_begins_at THEN m.happens_at ELSE m.reservation_begins_at END";
    $m2_stop  = "CASE WHEN m.happens_at + (m.duration||' seconds')::interval > m.reservation_ends_at THEN m.happens_at + (m.duration||' seconds')::interval ELSE m.reservation_ends_at END";
    $start = $this->happens_at > $this->reservation_begins_at ? $this->reservation_begins_at : $this->happens_at;
    $stop = $this->ends_at > $this->reservation_ends_at ? $this->ends_at : $this->reservation_ends_at;
    
    $q = Doctrine::getTable('Manifestation')->createQuery('m', true)
      ->leftJoin('m.Booking b')
      ->andWhere("$m2_start < ? AND $m2_stop > ?", array($stop, $start))
      ->andWhere('m.reservation_confirmed = ?', true)
      ->andWhere('m.blocking = ?', true)
      ->andWhere('(TRUE')
      ->andWhereIn('b.id',$rids)
      ->orWhereIn('m.location_id',$rids)
      ->andWhere('TRUE)');
    
    if ( !$this->isNew() )
      $q->andWhere('m.id != ?', $this->id);
    
    $this->setInCache('has-any-conflict', $q->count() > 0);
    return $this->hasAnyConflict();
    
    }
  }
  
  /**
    * Get all needed informations about the manifestation's gauges usage
    * @param array    $options: modeled on sales ledger's criterias
    * @return array   representing the informations for this manifestation
    *
    **/
  public function getInfosTickets($options = array())
  {
    try { $this->getFromCache('get-infos-tickets'); }
    catch ( liEvenementException $e )
    {
      return $this->setInCache('get-infos-tickets', $this->_getInfosTickets($options));
    }
  }
  private function _getInfosTickets($options = array())
  {
    if ( (isset($options['dates'][0]) || isset($options['dates'][1])) && (!isset($options['dates'][0]) || !isset($options['dates'][1])) )
      unset($options['dates']);
    
    $q = Doctrine::getTable('Ticket')->createQuery('tck')
      ->andWhere('manifestation_id = ?',$this->id)
      ->andWhere('tck.duplicating IS NULL');
        
    if ( isset($options['workspaces']) && $options['workspaces'] )
      $q->leftJoin('tck.Gauge g')
        ->andWhereIn('g.workspace_id',$options['workspaces']);
    
    if (!( isset($options['not-yet-printed']) && $options['not-yet-printed']))
      $q->andWhere('(tck.printed_at IS NOT NULL OR tck.cancelling IS NOT NULL OR tck.integrated_at IS NOT NULL)');
    else
      $q->leftJoin('tck.Transaction t')
        ->leftJoin('t.Payments p')
        ->andWhere('p.id IS NOT NULL');
    
    if ( isset($options['dates']) && is_array($options['dates']) )
    {
      if (!( isset($options['tck_value_date_payment']) && $options['tck_value_date_payment'] ))
        $q->andWhere('tck.printed_at IS NOT NULL AND tck.printed_at >= ? AND tck.printed_at < ? OR integrated_at IS NOT NULL AND tck.integrated_at >= ? AND tck.integrated_at < ? OR tck.cancelling IS NOT NULL AND tck.created_at >= ? AND tck.created_at < ?',array(
            $options['dates'][0], $options['dates'][1],
            $options['dates'][0], $options['dates'][1],
            $options['dates'][0], $options['dates'][1],
          ));
      else
      {
        if ( !$q->contains('LEFT JOIN t.Payments p') )
          $q->leftJoin('tck.Transaction t')
            ->leftJoin('t.Payments p');
        $q->andWhere('p.created_at >= ? AND p.created_at < ?',array(
            $options['dates'][0],
            $options['dates'][1],
          ))
          ->andWhere('p.id = (SELECT min(id) FROM Payment p2 WHERE transaction_id = t.id)');
      }
    }
    
    if ( sfContext::hasInstance()
      && !sfContext::getInstance()->getUser()->hasCredential('tck-ledger-all-users')
      && $context = sfContext::getInstance() )
      $q->andWhere('tck.sf_guard_user_id = ?',$context->getUser()->getId());
    else if ( isset($options['users']) && is_array($options['users']) && $options['users'][0] )
    {
      if ( $options['users'][''] ) unset($options['users']['']);
      if ( !isset($criterias['tck_value_date_payment']) )
        $q->andWhereIn('tck.sf_guard_user_id',$options['users']);
      else
      {
        if ( !$q->contains('LEFT JOIN t.Payments p') )
          $q->leftJoin('tck.Transaction t')
            ->leftJoin('t.Payments p');
        $q->andWhereIn('p.sf_guard_user_id',$options['users']);
      }
    }

    $tickets = $q->fetchArray();
    
    $r = array('taxes' => 0, 'value' => 0, 'qty' => 0, 'vat' => array());
    foreach ( $tickets as $ticket )
    {
      $r['value'] += $ticket['value'];
      $r['taxes'] += $ticket['taxes'];
      $r['qty']   += is_null($ticket['cancelling']) ? 1 : -1;
      
      if ( !isset($r['vat'][$ticket['vat']]) )
        $r['vat'][$ticket['vat']] = 0;
      
      // extremely weird behaviour, only for specific cases... it's about an early error in the VAT calculation in e-venement
      $r['vat'][$ticket['vat']] += sfConfig::get('app_ledger_sum_rounding_before',false) && strtotime($ticket['printed_at']) < sfConfig::get('app_ledger_sum_rounding_before')
        ? $ticket['value']+$ticket['taxes'] - ($ticket['value']+$ticket['taxes']) / (1+$ticket['vat'])
        : round($ticket['value'] + $ticket['taxes'] - ($ticket['value']+$ticket['taxes']) / (1+$ticket['vat']),2);
    }
    
    // rounding VAT
    foreach ( $r['vat'] as $rate => $value )
      $r['vat'][$rate] = round($value,2);
    
    return $r;
  }
  
  public function isAccessibleBy(sfSecurityUser $user, $confirm_needed = true)
  {
    // confirmation
    if (!( $confirm_needed && $this->reservation_confirmed ))
      return false;
    
    // meta event
    if ( !in_array($this->Event->meta_event_id, array_keys($user->getMetaEventsCredentials())) )
      return false;
    
    // workspaces
    $cpt = 0;
    foreach ( $this->Gauges as $gauge )
    if ( !in_array($gauge->workspace_id, array_keys($user->getWorkspacesCredentials())) )
      $cpt++;
    if ( $cpt == 0 )
      return false;
    
    // prices
    if ( $this->Prices->count() == 0 )
      return false;
    foreach ( $this->Prices as $price )
    if ( !in_array($user->getId(), $price->Users->getPrimaryKeys()) )
      return false;
    
    return true;
  }
  
  public function getIcalPartial(vevent $e, $full = true, $only_pending = false)
  {
    // settings
    $alarms = sfConfig::get('app_synchronization'.($only_pending ? 'pending_alarms' : 'alarms'), array('when' => array('-1 hour'), 'what' => array('display')));
    if ( !isset($alarms['when']) )
      $alarms['when'] = array('-1 hour');
    if ( !is_array($alarms['when']) )
      $alarms['when'] = array($alarms['when']);
    if ( !isset($alarms['what']) )
      $alarms['what'] = array();
    if ( !is_array($alarms['what']) )
      $alarms['what'] = array($alarms['what']);
    foreach ( $alarms['what'] as $key => $type )
    {
      switch ( $type ) {
        case 'display':
          $alarms['what'][$key] = 'DISPLAY';
          break;
        case 'email':
          $alarms['what'][$key] = 'EMAIL';
          break;
        case 'audio':
          $alarms['what'][$key] = 'AUDIO';
          break;
      }
    }
    
    $e->setProperty('categories', $this->Event->EventCategory );
    $e->setProperty('last-modified', date('YmdTHis',strtotime($this->updated_at)) );
    
    $time = strtotime($this->happens_at);
    $start = array('year'=>date('Y',$time),'month'=>date('m',$time),'day'=>date('d',$time),'hour'=>date('H',$time),'min'=>date('i',$time),'sec'=>date('s',$time),'tz'=>date('T'));
    $e->setProperty('dtstart', $start);
    
    $time = strtotime($this->ends_at);
    $stop = array('year'=>date('Y',$time),'month'=>date('m',$time),'day'=>date('d',$time),'hour'=>date('H',$time),'min'=>date('i',$time),'sec'=>date('s',$time),'tz'=>date('T'));
    $e->setProperty('dtend', $stop );
    
    $e->setProperty('summary', $this->Event);
    if ( $full )
      $e->setProperty('url', url_for('manifestation/show?id='.$this->id,true));
    
    $location = array((string)$this->Location);
    if ( $this->Location->city )
    foreach ( array('address', 'postalcode', 'city', 'country') as $prop )
      $location[] = $this->Location->$prop;
    $e->setProperty('location', implode(', ', $location));
    
    // extra properties
    $e->setProperty('status', 'CONFIRMED');
    if ( $full )
    {
      $client = sfConfig::get('project_about_client',array());
      $e->setProperty('description', $client['name'].(!$nourl ? "\nURL: ".url_for('manifestation/show?id='.$this->id, true) : ''));
      $e->setProperty('transp', $request->hasParameter('transp') ? 'TRANSPARENT' : 'OPAQUE');
      
      $orgs = array();
      if ( $this->Organizers->count() > 0 )
      {
        $email = '';
        foreach ( $this->Organizers as $org )
        {
          $orgs[] = (string)$org;
          if ( $org->email )
            $email = $org->email;
          elseif ( !$email )
            $email = $org->url;
        }
        $e->setProperty('organizer', $email, array('CN' => implode(', ', $orgs)));
      }
      
      // preparing email alerts
      $to = array();
      if ( in_array('EMAIL', $alarms['what']) )
      {
        foreach ( $this->Organizers as $org )
        if ( $org->email )
          $to[] = $org->email;
        if ( $this->contact_id && ($this->Applicant->sf_guard_user_id || $this->Applicant->email) )
          $to[] = $this->Applicant->sf_guard_user_id ? $this->Applicant->User->email_address : $this->Applicant->email;
      }
      
      // alarms
      foreach ( $alarms['when'] as $when )
      foreach ( $alarms['what'] as $what )
      {
        if ( $what == 'EMAIL' && count($to) == 0 )
          continue;
        
        $a = &$e->newComponent( 'valarm' );
        
        if ( $what == 'EMAIL' )
        foreach ( $to as $email )
          $a->setProperty('attendee', $email);
        
        $a->setProperty('action', $what);
        
        $time = strtotime($when, strtotime($this->happens_at)) - date('Z');
        $datetime = array('year'=>date('Y',$time),'month'=>date('m',$time),'day'=>date('d',$time),'hour'=>date('H',$time),'min'=>date('i',$time),'sec'=>date('s',$time));
        $a->setProperty('trigger', $datetime);
      }
    }
    
    return $e;
  }
  public function getIcal($full = true, $use_cache = true)
  {
    // filename
    $caldir   = sfConfig::get('sf_module_cache_dir').'/calendars/';
    $calfile = $this->event_id;
    $calfile .= '-';
    if ( sfContext::hasInstance() )
      $calfile .= sfContext::getInstance()->getUser()->isAuthenticated() ? sfContext::getInstance()->getUser()->getGuardUSer()->username : 'none';
    $calfile .= '.ics';
    
    $v = new vcalendar;
    $v->setConfig(array(
      'directory' => $caldir,
      'filename'  => $calfile,
    ));
    
    if ( file_exists($caldir.$calfile)
      && strtotime($this->happens_at) <= filemtime($caldir.$calfile)
      && $use_cache
    )
      $v->parse();
    else
    {
      $v->addComponent($this->getIcalPartial($v->newComponent( 'vevent' ), $full));
      
      if ( ! file_exists(dirname($caldir)) )
      {
        mkdir(dirname($caldir));
        chmod(dirname($caldir),0777);
      }
      if ( ! file_exists($caldir) )
      {
        mkdir($caldir);
        chmod($caldir,0777);
      }
      if ( file_exists($caldir.'/'.$calfile) )
        unlink($caldir.'/'.$calfile);
      
      $v->saveCalendar();
      chmod($caldir.'/'.$calfile,0777);
    }

    return $v->createCalendar();
  }
  
  public function getCacheTimeout()
  {
    $interval = sfConfig::get('app_cacher_timeout', '1 day ago');
    $rand = rand(2,11);
    if ( strtotime($this->ends_at) > time() && strtotime($this->happens_at) < time() )
      $interval = '6 hours '.$rand.' minutes ago';
    elseif ( strtotime($this->ends_at) < time() ) // in the past
    {
      $buf = time() - strtotime($this->ends_at);
      if ( $buf/60/60/24/7 <= 1 ) // less than 1 week ago
        $interval = '1 day '.$rand.' minutes ago';
      elseif ( $buf/60/60/24/30 <= 1 ) // between 1 week & 1 month ago
        $interval = '3 days '.$rand.' minutes ago';
      elseif ( $buf/60/60/24/90 <= 1 ) // between 1 month & 3 month ago
        $interval = ($buf/60/60/90+7).' days ago';
      else // more than 3 month ago
        $interval = '17 days '.$rand.' minutesago';
    }
    elseif ( strtotime($this->ends_at) > time() ) // in the future
    {
      $buf = strtotime($this->happens_at) - time();
      if ( $buf/60/60/24/7 <= 1 ) // less than 1 week ago
        $interval = '1 day '.$rand.' minutes ago';
      elseif ( $buf/60/60/24/30 <= 1 ) // between 1 week & 1 month ago
        $interval = '3 days '.$rand.' minutes ago';
      elseif ( $buf/60/60/24/90 <= 1 ) // between 1 month & 3 month ago
        $interval = ($buf/60/60/90+7).' days ago';
      else // more than 3 month ago
        $interval = '17 days '.$rand.' minutesago';
    }

    return $interval;
  }
}


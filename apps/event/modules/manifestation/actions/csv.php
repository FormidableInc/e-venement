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
  sfContext::getInstance()->getConfiguration()->loadHelpers(array('Number','I18N'));
  $params = OptionCsvForm::getDBOptions();
  
  if ( !method_exists($this->getRoute(), 'getObject') )
  { // extract manifestations' data from list
    $this->type = 'manifestations_list';
    
    $q = $this->buildQuery()->limit(500);
    $this->lines = array();
    foreach ( $q->execute() as $manif )
    {
      $booking = array();
      foreach ( $manif->Booking as $location )
        $booking[] = (string)$location;
      
      $extra = array();
      foreach ( $manif->ExtraInformations as $info )
        $extra[] = $info->name.($info->value ? ' -> '.$info->value : '').' ['.($info->checked ? 'x' : '').']';
      
      $this->lines[] = array(
        'manifid'          => $manif->id,
        'meta_event'  => (string)$manif->Event->MetaEvent,
        'event'       => (string)$manif->Event,
        'date_from'   => $manif->happens_at,
        'date_to'     => $manif->ends_at,
        'duration'    => $manif->duration_h_r,
        'age_min'     => $manif->Event->age_min_h_r,
        'age_max'     => $manif->Event->age_max_h_r,
        'event_description' => $manif->Event->description,
        'category'    => $manif->Event->EventCategory,
        'location'    => (string)$manif->Location,
        'reservation_from'  => $manif->reservation_begins_at,
        'reservation_to'    => $manif->reservation_ends_at,
        'applicant'   => (string)$manif->Applicant,
        'booking'     => implode(' + ', $booking),
        'confirmed'   => $manif->reservation_confirmed ? __('yes', null, 'sf_admin') : __('no', null, 'sf_admin'),
        'description' => $manif->description,
        'extra_informations' => implode("\n", $extra),
      );
    }
    
    $this->options = array(
      'ms' => in_array('microsoft',$params['option']),
      'tunnel' => false,
      'noheader' => false,
      'fields'   => array_keys($this->lines[count($this->lines)-1]),
    );
    
    $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename=manifestations.csv;');
  }
  else // extract data from one manifestation
  {
    $this->type = 'spectators_list';
    
    $this->manifestation = $this->getRoute()->getObject();
    $this->spectators = $this->getSpectators($this->manifestation->id, true);
    
    $this->prices = array();
    foreach ( $this->spectators as $spectator )
    foreach ( $spectator->Tickets as $ticket )
    if ( !isset($this->prices['price_'.$ticket->price_id]) )
    {
      $this->prices['price_'.$ticket->price_id] = $ticket->price_name.' ('.format_currency($ticket->value,$this->getContext()->getConfiguration()->getCurrency()).')';
      $this->prices['price_'.$ticket->price_id.'_value'] = __('Value');
    }
    
    $this->lines = array();
    $tickets = new Doctrine_Collection('Ticket');
    foreach ( $this->spectators as $spectator )
    {
      // contact infos
      $this->lines[] = array(
        'contact'     => $spectator->Contact ? (string)$spectator->Contact : '',
        'organism'    => $spectator->Professional ? (string)$spectator->Professional->Organism : '',
        'department'  => $spectator->Professional ? (string)$spectator->Professional->department : '',
        'organism_an' => $spectator->Professional ? $spectator->Professional->Organism->administrative_number : '',
      );
      
      // prices infos
      foreach ( $this->prices as $key => $name )
        $this->lines[count($this->lines)-1][$key] = 0;
      
      // tickets infos
      $total = array('qty' => 0, 'value' => 0);
      $ticket_ids = array();
      foreach ( $spectator->Tickets as $ticket )
      if ( !$ticket->contact_id || $ticket->contact_id == $spectator->contact_id )
      {
        $this->lines[count($this->lines)-1]['price_'.$ticket->price_id]++;
        $this->lines[count($this->lines)-1]['price_'.$ticket->price_id.'_value'] += $ticket->value;
        $total['qty']++;
        $total['value'] += $ticket->value;
                
        // Ticket ids
        $ticket_ids[] = '#' . $ticket->id;
      }
      else
        $tickets[] = $ticket;
      
      $this->lines[count($this->lines)-1]['total_qty']    = $total['qty'];
      $this->lines[count($this->lines)-1]['total_value']  = $total['value'];
      $this->lines[count($this->lines)-1]['accounting']   = $spectator->Invoice->count() > 0 ? '#'.$spectator->Invoice[0]->id : '';
      $this->lines[count($this->lines)-1]['transaction']  = '#'.$spectator->id;
      sort($ticket_ids);
      $this->lines[count($this->lines)-1]['tickets'] = implode(' ', $ticket_ids);
    }
    
    // the tickets with a direct contact embedded
    foreach ( $tickets as $ticket )
    {
      // contact infos
      $this->lines[] = array(
        'contact'     => (string)$ticket->DirectContact,
        'organism'    => '',
        'department'  => '',
        'organism_an' => '',
      );
      
      // prices infos
      foreach ( $this->prices as $key => $name )
        $this->lines[count($this->lines)-1][$key] = 0;
      
      // totals
      $total['qty']   += $this->lines[count($this->lines)-1]['total_qty']    = 1;
      $total['value'] += $this->lines[count($this->lines)-1]['total_value']  = $ticket->value;
      
      // & co
      $this->lines[count($this->lines)-1]['accounting']   = '';
      $this->lines[count($this->lines)-1]['transaction']  = '#'.$ticket->transaction_id;

      // Ticket ids
      $this->lines[count($this->lines)-1]['tickets'] = '#' . $ticket->id;
    }
    
    // adding the last "total" line
    $totals = array(
      'contact'     => __('Total'),
      'organism'    => '',
      'department'  => '',
      'organism_an' => '',
      'accounting'  => '',
      'transaction' => '',
      'total_value' => 0,
      'total_qty'   => 0,
    );
    foreach ( $this->prices as $key => $name )
      $totals[$key] = 0;
    foreach ( $this->lines as $key => $line )
    foreach ( array_merge(array('total_qty', 'total_value',), array_keys($this->prices)) as $field )
      $totals[$field] += $line[$field];
    $this->lines[] = $totals;
    
    // formatting numbers w/ digits
    foreach ( $this->lines as $key => $line )
    {
      $this->lines[$key]['total_value'] = format_number($line['total_value']);
      foreach ( $this->prices as $name => $name )
        $this->lines[$key][$name] = format_number($line[$name]);
    }
    
    $this->options = array(
      'ms' => in_array('microsoft',$params['option']),
      'tunnel' => false,
      'noheader' => false,
      'fields'   => array_merge(array(
        'organism_an',
        'organism',
        'contact',
        'department',
        'transaction',
      ),array_keys($this->prices),array(
        'total_qty',
        'total_value',
        'accounting',
        'tickets'
      )),
    );
  }
  
  $this->outstream = 'php://output';
  $this->delimiter = $this->options['ms'] ? ';' : ',';
  $this->enclosure = '"';
  $this->charset   = sfConfig::get('software_internals_charset');

  sfConfig::set('sf_escaping_strategy', false);
  if ( $this->getContext()->getConfiguration()->getEnvironment() == 'dev' && $request->hasParameter('debug') )
  {
    $this->getResponse()->sendHttpHeaders();
    $this->setLayout('layout');
  }
  else
    sfConfig::set('sf_web_debug', false);

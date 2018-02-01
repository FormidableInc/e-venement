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
  $vars = array(
    'options',
    'delimiter',
    'enclosure',
    'outstream',
    'charset',
    'lines',
  );
  foreach ( $vars as $key => $value )
  {
    $vars[$value] = $$value;
    unset($vars[$key]);
  }
  
  switch ( $type ) {
  case 'spectators_list':
    $vars['options']['header'] = array_merge(array(
      'organism_an'   => __('Admin. ID'),
      'organism'      => __('Organism'),
      'contact'       => __('Contact'),
      'department'    => __('Department'),
    ),$prices,array(
      'total_qty'     => __('Qty'),
      'total_value'   => __('Value'),
      'transaction'   => __('Transaction'),
      'accounting'    => __('Acc.'),
      'tickets'       => __('Tickets'),
    ));
    break;
  case 'manifestations_list':
    $vars['options']['header'] = array(
      'manifid'            => __('Id'),
      'meta_event'    => __('Meta event'),
      'event'         => __('Event'),
      'date_from'     => __('Happens at'),
      'date_to'       => __('Ends at'),
      'duration'      => __('Duration'),
      'age_min'       => __('Age min'),
      'age_max'       => __('Age max'),
      'event_description' => __('Description'),
      'category'      => __('Category'),
      'location'      => __('Location'),
      'reservation_from'  => __('Booked from'),
      'reservation_to'    => __('Booked until'),
      'applicant'     => __('Applicant'),
      'booking'       => __('Bookings'),
      'confirmed'     => __('Confirmed'),
      'description'   => __('Memo'),
      'extra_informations' => __('Extra informations'),
    );
    break;
  }
  include_partial('global/csv',$vars);

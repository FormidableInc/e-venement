<?php 

  $soldout = false;
  $gauges = 0;
  
  foreach ($manifestation->Gauges as $gauge) {
    if ( $gauge->online ) {
      $gauges += $gauge->value;
    }
  }

  if ( $manifestation->sold_tickets + $manifestation->online_limit >= $gauges ) {
    $soldout = '<span class="closed">' . __('Sold Out') . '</span>';
  } else {
    $seats = $gauges - $manifestation->online_limit - $manifestation->sold_tickets;
    $soldout = format_number_choice('[1]%%SEATS%% available seat|(1,+Inf]%%SEATS%% available seats', array('%%SEATS%%' => $seats), $seats);
  }

  echo $soldout;

?>
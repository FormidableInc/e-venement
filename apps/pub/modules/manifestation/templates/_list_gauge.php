<?php 

  $soldout = false;
  $gauges = 0;
  
  foreach ($manifestation->Gauges as $gauge) {
    if ( $gauge->online ) {
      $gauges += $gauge->value;
    }
  }

  if ( $manifestation->sold_tickets + $manifestation->online_limit >= $gauges ) {
    $soldout = __('Sold Out');
  } else {
    $soldout = $gauges - $manifestation->online_limit - $manifestation->sold_tickets;
  }

  echo $soldout;

?>
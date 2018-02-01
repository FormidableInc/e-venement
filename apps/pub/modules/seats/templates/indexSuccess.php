<?php
  use_helper('Number');
  $prices = $seats = array();
  
  // preparing stuff to optimize fetching Seats from seated plans
  $prepare = array();
  $prepare[] = '?';
  $seated_plan_workspaces = new Doctrine_Collection('Workspace');
  foreach ( $seated_plans as $sp )
  {
    $prepare[] = '?';
    $seated_plan_workspaces[$sp->id] = $sp->Workspaces[0];
  }
  $arr = array_merge(array(0), $seated_plan_workspaces->getKeys());

  // optimized Seats fetching
  //$seat_records = new Doctrine_Collection('Seat');
  //foreach ( $seated_plans as $seated_plan )
  //  $seat_records->merge($seated_plan->Seats);
  $seat_records = Doctrine::getTable('Seat')->createQuery('s')
    ->leftJoin('s.Holds h')
    ->leftJoin('s.SeatedPlan sp WITH sp.id IN ('.implode(',', $prepare).')', array_values($arr)) // 0 is a workaround to avoid empty "IN" condition
    ->andWhere('sp.id IS NOT NULL')
    ->execute()
  ;
  
  foreach ( $seat_records as $seat )
  if (!(
       isset($occupied)
    && isset($occupied[$seat->id])
    && $occupied[$seat->id]['transaction_id'] === false
  ))
  {
    if ( !isset($prices[$seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->id]) )
    {
      $prices[$seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->id] = array();
      foreach ( $seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->Manifestation->PriceManifestations as $pm )
      if ( $pm->Price->isAccessibleBy($sf_user) )
        $prices[$seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->id][$pm->price_id] = $pm->value;
      foreach ( $seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->PriceGauges as $pg )
      if ( $pg->Price->isAccessibleBy($sf_user) )
        $prices[$seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->id][$pg->price_id] = $pg->value;
    }
    
    $infos = array();
    $infos[] = $seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->group_name ? $seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->group_name : $seated_plan_workspaces[$seat->seated_plan_id];
    if ( count($prices[$seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->id]) > 0 )
    $infos[] = min($prices[$seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->id]) != max($prices[$seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->id])
      ? __('from %%from%% to %%to%%', array(
        '%%from%%' => format_currency(min($prices[$seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->id]), $sf_context->getConfiguration()->getCurrency()),
        '%%to%%'   => format_currency(max($prices[$seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->id]), $sf_context->getConfiguration()->getCurrency()),
      ))
      : format_currency(min($prices[$seated_plan_workspaces[$seat->seated_plan_id]->Gauges[0]->id]), $sf_context->getConfiguration()->getCurrency())
    ;
    
    $seats[] = array(
      'type'      => 'seat',
      'position'  => array(
        'x'         => $seat->x,
        'y'         => $seat->y,
      ),
      'diameter'  => $seat->diameter,
      'name'      => $seat->name,
      'id'        => $seat->id,
      'class'     => $seat->class,
      'rank'      => '',
      'info'      => implode(', ',$infos),
      'seated_plan_id' => $seat->seated_plan_id,
      'occupied'  => isset($occupied) && isset($occupied[$seat->id]) ? $occupied[$seat->id] : false,
    );
  }
  
  if ( sfConfig::get('sf_web_debug', false) )
    echo '<pre>'.print_r($seats, true).'</pre>';
  else
    echo json_encode($seats);

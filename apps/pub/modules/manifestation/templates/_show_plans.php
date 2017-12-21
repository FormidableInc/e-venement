<?php
  $plans = array();
  foreach ( $manifestation->getRawValue()->Gauges as $gauge )
  if ( $gauge->online && $gauge->Workspace->seated )
  {
    $sp = $gauge->seated_plan;
    if (! $sp instanceof SeatedPlan )
      continue;
    
    if ( !isset($plans[$sp->picture_id]) )
      $plans[$sp->picture_id] = array(
        'seated_plan' => $sp,
        'gauges' => array(),
      );
    $plans[$sp->picture_id]['gauges'][] = $gauge;
  }
?>

<?php if ( count($plans) > 0 ): ?>

<?php use_stylesheet('/private/event-seated-plan') ?>
<?php use_javascript('jquery.overscroll.min.js') ?>
<div id="plans-loading">
  <?php echo pubConfiguration::getText('app_texts_seated_plan_loading', __('Seated plan loading, thanks for your patience...')); ?>
</div>
<div id="plans" data-manifestation-id="<?php echo $manifestation->id ?>" class="gauge">
<?php foreach ( $plans as $plan ): ?>
<?php if ( isset($plan['seated_plan']) && $plan['seated_plan'] instanceof SeatedPlan ): ?>
  <?php include_partial('global/magnify') ?>
  <div class="plan-<?php echo $plan['seated_plan']->id ?> gauge">
    <?php echo $plan['seated_plan']->render($plan['gauges'], array(
      'app' => sfContext::getInstance()->getConfiguration()->getApplication(),
      'get-seats' => 'seats/index',
      'match-seated-plan' => false,
      'add-data-src' => true,
    )) ?>
  </div>
<?php endif ?>
<?php endforeach ?>
  <div class="infos">
    <a href="<?php echo url_for('ticket/modTickets?manifestation_id='.$manifestation->id) ?>" id="ajax-init-data"></a>
    <a href="<?php echo url_for('cart/show') ?>" id="go-to-cart"></a>
    <span class="no-price"><?php echo __('Remove this ticket') ?></span>
    <a href="<?php echo url_for('ticket/getOrphans') ?>" id="ajax-pre-submit"></a>
  </div>
</div>
<div class="legend"><?php include_partial('show_plans_legend') ?></div>

<?php endif ?>

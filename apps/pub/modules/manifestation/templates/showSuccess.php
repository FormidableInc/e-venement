<?php include_partial('global/ariane', array('active' => 1)) ?>
<?php include_partial('global/flashes') ?>
<?php include_partial('show_title', array('manifestation' => $manifestation)) ?>
<?php include_partial('event/show_picture', array('event' => $manifestation->Event)) ?>
<?php include_partial('show_ical_qrcode', array('manifestation' => $manifestation)) ?>
<?php if ( $manifestation->Event->picture_id ): ?>
  <?php 
  $app = sfContext::getInstance()->getConfiguration()->getApplication();
  $sf_response->addMeta('og:image', array(
      'property'=>'og:image',
      'content'=>$manifestation->Event->Picture->getUrl(array('app'=>$app, 'absolute'=>true))
    )); 
  ?>
  <?php $sf_response->addMeta('itemprop:image', array(
      'itemprop'=>'image',
      'content'=>$manifestation->Event->Picture->getUrl(array('app'=>$app, 'absolute'=>true))
    )); ?>
<?php endif ?>
<?php $sf_response->addMeta('og:description', array('property'=>'og:description', 'content'=>$manifestation->getSocialDescription())); ?>


<?php if ( sfConfig::get('app_options_synthetic_plans', false) ): ?>
<div class="synthetic">

  <?php use_stylesheet('pub-manifestation-synthetic?'.date('Ymd')) ?>
  <?php use_javascript('pub-manifestation-synthetic?'.date('Ymd')) ?>
  <?php use_javascript('pub-seated-plan?'.date('Ymd')) ?>

  <?php include_partial('show_synthetic_full', array('gauges' => $gauges)) ?>

  <?php
    $seated = new Doctrine_Collection('Gauge');
    $unseated = new Doctrine_Collection('Gauge');
    foreach ( $gauges as $gauge )
    if (!( $gauge->Workspace->seated && $gauge->getSeatedPlan() ))
      $unseated[] = $gauge;
    else
      $seated[] = $gauge;
  ?>
  <div id="tickets">
    <?php
      if ( $unseated->count() > 0 )
        include_partial('show_gauges', array('gauges' => $unseated, 'manifestation' => $manifestation, 'form' => $form, 'mcp' => $mcp, 'show_name' => true, ));
    ?>
    <?php include_partial('show_named_tickets', array('manifestation' => $manifestation)) ?>
  </div>
  <?php if ( $seated->count() > 0 ): ?>
  <div id="container">
    <h4 data-tab="#plans"><?php echo __('Choice by the seating') ?></h4>
    <h4 class="hidden" data-tab="#categories"><?php echo __('Automatic choice by category') ?></h4>
    <div class="tab" id="plans">
      <div class="li-content">
        <?php include_partial('show_plans', array('manifestation' => $manifestation)) ?>
        <div class="description"><?php echo nl2br(pubConfiguration::getText('app_texts_synthetic_plans', '')); ?></div>
        <div class="clear"></div>
      </div>
    </div>
    <div class="tab hidden" id="categories">
      <div class="li-content">
        <?php include_partial('show_categories', array('manifestation' => $manifestation, 'gauges' => $gauges)) ?>
        <div class="description"><?php echo nl2br(pubConfiguration::getText('app_texts_synthetic_categories', '')); ?></div>
        <div class="clear"></div>
      </div>
    </div>
  </div>
  <?php endif ?>
  <div class="clear"></div>
  <?php include_partial('show_footer', array('manifestation' => $manifestation)) ?>
  <?php include_partial('global/show_links', array('objects' => $manifestation)) ?>

</div>
<?php else: ?>

  <?php include_partial('show_gauges', array('gauges' => $gauges, 'manifestation' => $manifestation, 'form' => $form, 'mcp' => $mcp, )) ?>
  <?php include_partial('show_footer', array('manifestation' => $manifestation)) ?>
  <?php include_partial('show_ease') ?>
  <?php include_partial('global/show_links', array('objects' => $manifestation)) ?>

<?php endif ?>

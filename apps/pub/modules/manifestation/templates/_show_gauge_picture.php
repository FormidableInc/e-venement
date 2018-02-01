<?php if ( $sp = $manifestation->Location->getWorkspaceSeatedPlan($gauge->workspace_id) ): ?>
  <?php $conf = sfConfig::get('app_tickets_vel', array()) ?>
  <?php if ( isset($conf['full_seating_by_customer']) && $conf['full_seating_by_customer'] ): ?>
  <?php use_stylesheet('event-seated-plan?'.date('Ymd')) ?>
  <?php use_javascript('event-seated-plan?'.date('Ymd')) ?>
  <?php use_stylesheet('/private/event-seated-plan?'.date('Ymd')) ?>
  <?php include_partial('global/magnify') ?>
  <div class="full-seating">
    <a href="<?php echo url_for('seats/index?id='.$sp->id.($gauge->id ? '&gauge_id='.$gauge->id : '')) ?>"
       class="picture seated-plan on-demand" <?php if ( $gauge->id ): ?>id="seated-plan-gauge-<?php echo $gauge->id ?>"<?php endif ?>
       style="background-color: <?php echo $sp->background ?>;"
    >
      <?php echo $sp->getRaw('Picture')->getHtmlTag(array(
        'title' => $sp->Picture,
        'width' => $sp->ideal_width,
        'app'   => sfContext::getInstance()->getConfiguration()->getApplication(),
      )) ?>
    </a>
    <a href="<?php echo url_for('ticket/addSeat?id='.$gauge->id) ?>" class="add-seat"></a>
    <a href="<?php echo url_for('ticket/removeTicket?id='.$gauge->id) ?>" class="remove-ticket"></a>
    <a href="<?php echo url_for('seats/index?id='.$sp->id.($gauge->id ? '&gauge_id='.$gauge->id : '')) ?>" class="load-data"></a>
  </div>
  <?php else: ?>
  <div class="picture">
    <p><a href="#" onclick="javascript: $(this).closest('.picture').find('.seated-plan').slideToggle('medium'); $(this).toggleClass('opened'); return false;"><?php echo __('Display venue') ?></a></p>
    <p class="seated-plan"><?php echo $sp->getRawValue()->OnlinePicture->getHtmlTag(array('app' => sfContext::getInstance()->getConfiguration()->getApplication(), 'title' => $gauge->Workspace)) ?></p>
  </div>
  <?php endif ?>
<?php endif ?>

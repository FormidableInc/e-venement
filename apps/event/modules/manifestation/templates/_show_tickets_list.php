<?php use_helper('Number') ?>

<?php $arr = isset($form) ? array('form' => $form) : array('prices' => $prices) ?>

<?php include_partial('show_print_part',array('tab' => 'tickets')) ?>
<?php include_partial('show_tickets_list_ordered',$arr); ?>
<?php include_partial('show_tickets_list_printed',$arr); ?>
<?php if (!sfConfig::get('app_ticketting_hide_demands')): ?>
<?php include_partial('show_tickets_list_asked',$arr); ?>
<?php endif ?>

<?php if ( false && sfConfig::get('app_ticketting_dematerialized') ): ?>
  <?php include_partial('show_tickets_list_controlled',array('form' => $form)) ?>
  <?php include_partial('show_tickets_list_batch',array('form' => $form)) ?>
<?php endif ?>

<?php use_helper('Date') ?>
<?php use_helper('CrossAppLink') ?>
<?php include_partial('filters',array('form' => $form)) ?>
<div class="ui-widget ui-corner-all ui-widget-content cards">
  <div class="ui-widget-header ui-corner-all fg-toolbar">
    <h1><?php echo __('Membercard creation from %%from%% to %%to%%',array('%%from%%' => format_date($dates['from']), '%%to%%' => format_date($dates['to']))) ?></h1>
    <?php include_partial('attendance/filters_buttons') ?>
  </div>
  <?php include_partial('global/chart_help'); ?>
  <?php if ( $sf_user->hasCredential('stats-pr-cards') ): ?>
   <?php include_partial('global/chart_jqplot', array(
            'id'    => 'cards',
            'data'  => cross_app_url_for('stats', 'cards/json'),
            'width' => '900',
            'name'  => __('Member cards')
          )) 
    ?>
  
<?php endif ?>
</div>

<?php use_javascript('/js/jqplot/plugins/jqplot.pieRenderer.js') ?>
<?php use_javascript('/js/jqplot/plugins/jqplot.cursor.js') ?>
<?php use_javascript('stats-cards?'.date('Ymd')) ?>


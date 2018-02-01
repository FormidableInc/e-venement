<?php use_javascript('pub-cart?'.date('Ymd')) ?>
<?php include_partial('global/flashes') ?>
<?php use_helper('Number'); ?>
<?php use_helper('Date') ?>
<script type="text/javascript"><!--
$(document).ready(function(){
  if ( LI.pubNamedTicketsInitialization != undefined )
    LI.pubNamedTicketsInitialization();
});
--></script>

<div id="title">
  <h1><?php echo __('Command summary') ?></h1>
  <p><b><?php echo __('Transaction number') ?>:</b> #<?php echo $transaction->id ?> <b><?php echo __('Edition date') ?>:</b> <?php echo format_datetime(date('Y-m-d H:i:s'),'f') ?></p>
  <p><b><?php echo __('Contact') ?>:</b> <?php echo $sf_user->hasContact() ? $sf_user->getContact() : 'N/A' ?></p>
</div>

<?php include_partial('global/ariane', array('active' => $current_transaction ? 2 : 0)) ?>

<?php $last = array('event_id' => 0, 'manifestation_id' => 0, 'gauge_id' => 0) ?>
<?php $nb_ws = 0 ?>
<?php $total = array('qty' => 0, 'value' => 0, 'taxes' => 0, 'mc_qty' => 0, 'mc_value' => 0) ?>

<?php $for_links = array() ?>

<table id="command">
<tbody>
<?php foreach ( $events as $event ): ?>
<?php foreach ( $event->Manifestations as $manif ): ?>
<?php foreach ( $manif->Gauges as $gauge ): ?>
<?php foreach ( $gauge->Tickets as $ticket ): ?>
<?php $for_links[] = $ticket->Manifestation ?>
<tr
  data-manifestation-id="<?php echo $manif->id ?>"
  data-gauge-id="<?php echo $gauge->id ?>"
  data-event-id="<?php echo $event->id ?>"
  id="ticket-<?php echo $ticket->id ?>"
  class="tickets <?php if ( in_array($gauge->id,$sf_data->getRaw('errors')) ) echo 'overbooked' ?>"
>
  <?php if ( sfConfig::get('app_options_synthetic_plans', false) ): ?>
  <td class="picture"><?php echo $event->Picture->getRawValue()->render(array('app' => sfContext::getInstance()->getConfiguration()->getApplication())) ?></td>
  <td class="event">
    <p><?php echo $event ?></p>
    <p><?php echo $manif->getFormattedDate() ?></p>
  </td>
  <td class="manifestation">
    <?php include_partial('manifestation/show_named_tickets', array('manifestation' => $manif, 'ticket' => $ticket, 'transaction' => $transaction, 'display_continue' => false, 'display_mods' => false)) ?>
  </td>
  <?php else: ?>
  <td class="picture"><?php echo $event->Picture->getRawValue()->render(array('app' => sfContext::getInstance()->getConfiguration()->getApplication())) ?></td>
  <td class="event"><?php if ( $last['event_id'] != $event->id ) { $last['event_id'] = $event->id; echo $event; } ?></td>
  <td class="manifestation"><?php if ( $last['manifestation_id'] != $manif->id ) { $last['manifestation_id'] = $manif->id; echo $manif->getFormattedDate(); } ?></td>
  <?php endif ?>
  <td class="workspace"><?php if ( $manif->Gauges->count() > 1 && $last['gauge_id'] != $gauge->id || sfConfig::get('app_options_synthetic_plans', false) ): ?>
    <?php echo $gauge->Workspace->on_ticket ? $gauge->Workspace->on_ticket : $gauge->Workspace ?>
    <?php $nb_ws++ ?>
  <?php endif ?></td>
  <?php
    if ( $ticket->price_id )
    {
      $total[$ticket->Price->member_card_linked ? 'mc_qty' : 'qty']++;
      $total[$ticket->Price->member_card_linked ? 'mc_value' : 'value'] += $ticket->value;
      $total['taxes'] += $ticket->taxes;
    }
  ?>
  <?php include_partial('show_ticket',array('ticket' => $ticket)) ?>
  <?php if ( sfConfig::get('app_options_synthetic_plans', false) && $current_transaction ): ?>
  <td class="linked-stuff"><?php include_partial('show_linked_stuff', array('ticket' => $ticket))  ?></td>
  <?php endif ?>
  <?php $last['gauge_id'] = $gauge->id; ?>
  <td class="mod"><?php if ( $current_transaction && $manif->IsNecessaryTo->count() == 0 && $manif->id != $auto_add_id ): ?>
    <?php echo link_to(__('modify'),'manifestation/show?id='.$manif->id) ?>
    <?php echo link_to(__('delete'),'manifestation/del?gauge_id='.$gauge->id.'&price_id='.$ticket->price_id) ?>
  <?php endif ?></td>
</tr>
<?php endforeach ?>
<?php endforeach ?>
<?php endforeach ?>
<?php endforeach ?>
<?php foreach ( $member_cards as $mc ): ?>
<tr id="mct-<?php echo $mc->member_card_type_id ?>" class="member_cards" data-mct-id="<?php echo $mc->member_card_type_id ?>">
  <td class="picture"></td>
  <td class="event"><?php echo $mc->MemberCardType->description ? $mc->MemberCardType->description : $mc->MemberCardType ?></td>
  <td class="manifestation"><span class="mct-<?php echo $mc->member_card_type_id ?>"><?php echo sfConfig::get('app_member_cards_show_expire_at', true) ? format_date($mc->expire_at,'P') : '' ?></span></td>
  <td class="workspace"></td>
  <td class="tickets"><span data-mct-id="<?php echo $mc->member_card_type_id ?>" class="mct-<?php echo $mc->member_card_type_id ?>"><?php echo $mc->MemberCardType ?></span></td>
  <?php $value = $mc->MemberCardType->value; foreach ( $mc->BoughtProducts as $bp ) $value += $bp->value + $bp->shipping_fees; ?>
  <?php $total['qty']++; $total['value'] += $value ?>
  <?php if ( !sfConfig::get('app_options_synthetic_plans', false) ): ?>
  <td class="value"><?php echo format_currency($value,$sf_context->getConfiguration()->getCurrency()) ?></td>
  <td class="qty">1</td>
  <?php endif ?>
  <td class="total"><?php echo format_currency($value,$sf_context->getConfiguration()->getCurrency()) ?></td>
  <td class="extra-taxes" title="<?php echo __('Booking fees') ?>"></td>
  <?php if ( sfConfig::get('app_options_synthetic_plans', false) && $current_transaction ): ?>
  <td class="linked-stuff"></td>
  <?php endif ?>
  <td class="mod"><?php if ( $current_transaction ): ?>
    <?php echo link_to(__('modify'),'card/index') ?>
    <?php echo link_to(__('delete'),'card/del?id='.$mc->id) ?>
  <?php endif ?></td>
</tr>
<?php endforeach ?>
<?php foreach ( $products as $product ): ?>
<?php if ( !$product->member_card_id ): ?>
<?php if ( $product->product_declination_id ) $for_links[] = $product->Declination->Product ?>
<tr class="products">
  <td class="picture"></td>
  <td class="event"><?php echo $product->product_declination_id ? $product->Declination->Product->Category : '' ?></td>
  <td class="manifestation"><?php echo $product->product_declination_id && $product->Declination->Product->short_name ? $product->Declination->Product->short_name : $product ?></td>
  <td class="workspace"><?php
    echo $product->integrated_at && strtotime($product->integrated_at) <= time() && trim($product->getRawValue()->description_for_buyers)
      || $transaction->getPaid().'' >= ''.$transaction->getPrice(true, true) // the .'' is a hack for float values
      ? $product->getRawValue()->description_for_buyers
      : $product->declination
  ?></td>
  <td class="tickets">
    <span class="price-<?php echo $product->price_id ?>" data-price-id="<?php echo $product->price_id ?>">
      <?php if ( $product->price_id ): ?>
        <?php echo $product->Price->description ? $product->Price->description : $product->Price ?>
      <?php else: ?>
        <?php echo $product->price_name ?>
      <?php endif ?>
    </span>
  </td>
  <?php $total['qty']++; $total['value'] += $product->value ?>
  <?php if ( !sfConfig::get('app_options_synthetic_plans', false) ): ?>
    <td class="value"><?php echo format_currency($product->value,$sf_context->getConfiguration()->getCurrency()) ?></td>
    <td class="qty">1</td>
  <?php endif ?>
  <td class="total"><?php echo format_currency($product->value,$sf_context->getConfiguration()->getCurrency()) ?></td>
  <td class="extra-taxes" title="<?php echo __('Booking fees') ?>">
    <?php echo $product->shipping_fees ? format_currency($product->shipping_fees,$sf_context->getConfiguration()->getCurrency()) : '' ?>
    <?php $total['taxes'] += $product->shipping_fees ?>
  </td>
  <?php if ( sfConfig::get('app_options_synthetic_plans', false) && $current_transaction ): ?>
  <td class="linked-stuff"></td>
  <?php endif ?>
  <td class="mod">
    <?php if ( $current_transaction
      && $product->product_declination_id
      && $product->Declination->Product->product_category_id
      && $product->Declination->Product->Category->online
      && $current_transaction
      && !$product->ticket_id
    ): ?>
      <?php echo link_to(__('modify'),'store/edit?id='.$product->Declination->product_id) ?>
      <?php echo link_to(__('delete'),'store/del?id='.$product->product_declination_id) ?>
    <?php endif ?>
  </td>
</tr>
<?php endif ?>
<?php endforeach ?>
</tbody>
<?php $recalculated = array(
    'total'     => $transaction->getPrice(true, true),
    'withmc'    => $transaction->getTicketsLinkedToMemberCardPrice(true),
    'mc_qty'    => $transaction->getMCPaymentCount(true),
) ?>
<tfoot>
  <?php $total['value'] += $total['mc_value'] ?>
  <?php $total['qty']   += $total['mc_qty'] ?>
  <?php if ( $total['mc_qty'] && ($total['mc_value'] < 0 || count($member_cards) == 0) ): ?>
  <tr class="total">
    <td class="picture"></td>
    <td class="type"><?php echo __('Total') ?></td>
    <td></td>
    <td></td>
    <?php if ( !sfConfig::get('app_options_synthetic_plans', false) ): ?>
      <td></td>
      <td></td>
    <?php endif ?>
    <td class="qty"><?php echo $total['qty'] ?></td>
    <td class="total"><?php echo format_currency($total['value'],$sf_context->getConfiguration()->getCurrency()); ?></td>
    <?php if ( sfConfig::get('app_options_synthetic_plans', false) && $current_transaction ): ?>
    <td class="linked-stuff"></td>
    <?php endif ?>
    <td></td>
  </tr>
  <?php if ( $recalculated['withmc'] > 0 ): ?>
  <tr class="mc">
    <td class="picture"></td>
    <td class="type"><?php echo __("Passed on member card") ?></td>
    <td></td>
    <td></td>
    <?php if ( !sfConfig::get('app_options_synthetic_plans', false) ): ?>
      <td></td>
      <td></td>
    <?php endif ?>
    <td class="qty"><?php echo $recalculated['mc_qty'] ?></td>
    <td class="total"><?php echo format_currency(-$recalculated['withmc'],$sf_context->getConfiguration()->getCurrency()); ?></td>
    <?php if ( sfConfig::get('app_options_synthetic_plans', false) && $current_transaction ): ?>
    <td class="linked-stuff"></td>
    <?php endif ?>
    <td></td>
  </tr>
  <?php endif ?>
  <?php endif ?>
  <tr class="topay">
    <td class="picture"></td>
    <td class="type"><?php echo $total['mc_qty'] ? __('To pay') : __('Total') ?></td>
    <td></td>
    <td></td>
    <?php if ( !sfConfig::get('app_options_synthetic_plans', false) ): ?>
      <td></td>
      <td></td>
    <?php endif ?>
    <td class="qty"><?php echo $total['qty'] - $recalculated['mc_qty'] ?></td>
    <td class="total"><?php echo format_currency($total['value'] - $recalculated['withmc'],$sf_context->getConfiguration()->getCurrency()); ?></td>
    <td class="extra-taxes"><?php echo format_currency($total['taxes'],$sf_context->getConfiguration()->getCurrency()); ?></td>
    <?php if ( sfConfig::get('app_options_synthetic_plans', false) && $current_transaction ): ?>
    <td class="linked-stuff"></td>
    <?php endif ?>
    <td class="total-total"><?php echo format_currency($recalculated['total'],$sf_context->getConfiguration()->getCurrency()); ?></td>
  </tr>
</tfoot>
<thead>
  <tr>
    <td class="picture"></td>
    <td class="product"><?php echo __('Product') ?></td>
    <td class="declination"><?php echo __('Declination') ?></td>
    <td class="space"><?php if ( $nb_ws > 0 ) echo __('Space') ?></td>
    <td class="tickets"><?php echo __('Price') ?></td>
    <?php if ( !sfConfig::get('app_options_synthetic_plans', false) ): ?>
    <td class="value"><?php echo __('Unit price') ?></td>
    <td class="qty"><?php echo __('Qty') ?></td>
    <?php endif ?>
    <td class="total"><?php echo sfConfig::get('app_options_synthetic_plans', false) ? '' : __('Total') ?></td>
    <td class="extra-taxes" title="<?php echo __('Booking fees') ?>"><?php echo __('Fees') ?>*</td>
    <?php if ( sfConfig::get('app_options_synthetic_plans', false) && $current_transaction ): ?>
    <td class="linked-stuff"><?php echo __('Options') ?></td>
    <?php endif ?>
    <td class="mod"></td>
  </tr>
</thead>
</table>

<?php if ( !$current_transaction && $transaction->getPaid().'' < ''.$transaction->getPrice(true, true) || $current_transaction ): // the .'' is a hack for float values ?>
<?php include_partial('show_order', array('transaction' => $transaction)) ?>
<?php else: ?>
<?php include_partial('show_resend_email', array('transaction' => $transaction)) ?>
<?php endif ?>

<?php if ( !$current_transaction ): ?>

<div id="payments">
<h3><?php echo __('Payment status') ?>:</h3>
<?php include_partial('show_payments',array('transaction' => $transaction)) ?>
</div>

<div id="details">
<h3><?php echo __('Command status') ?>:</h3>
<?php include_partial('show_details',array('transaction' => $transaction)) ?>
</div>

<div id="transaction-content">
<h3><?php echo __('Content') ?>:</h3>
<?php include_partial('show_content',array('transaction' => $transaction)) ?>
</div>

<?php endif ?>

<div class="clear"></div>

<div id="cmd-links">
<?php include_partial('global/show_links', array('objects' => $for_links)); ?>
</div>
<?php if ( $current_transaction ): ?>
<?php include_partial('show_comment',array('transaction' => $transaction, 'form' => $form)) ?>
<?php endif ?>
<?php include_partial('show_bottom',array('end' => $end)) ?>

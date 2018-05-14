<?php if ( $transaction['invoice'] ): ?>
#<?php echo link_to(sfConfig::get('app_seller_invoice_prefix') . $transaction['invoice'], 'ticket/invoice?id='.$transaction['tid'], array('target' => 'blank', 'onclick' => 'javascript: setTimeout(function(){$("#transition").hide();},500);')) ?>
<?php endif ?>

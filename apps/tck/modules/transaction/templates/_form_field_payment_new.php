<?php echo $form->renderFormTag(url_for('transaction/complete?id='.$transaction->id), array(
  'autocomplete' => 'off',
  'target' => '_blank',
  'method' => 'get',
)) ?>
<?php echo $form->renderHiddenFields() ?>
<div class="field_payment_method_id field">
<?php echo $form['payment_method_id'] ?>
</div>
<p class="field_created_at field">
<?php echo $form['created_at']->renderLabel() ?>
<?php echo $form['created_at'] ?>
</p>
<p class="field_value field">
<?php echo $form['value']->renderLabel() ?>
<?php echo $form['value']->render(array('class' => 'for-board')) ?>
</p>
<p class="submit">
<button name="s" value="" class="ui-widget-content ui-state-default ui-corner-all ui-widget fg-button"><?php echo __('Add') ?></button>
</p>
<script type="text/javascript">
$(document).ready(function(){
  $('#li_transaction_field_payment_new form').submit(function(){
    if ( !$(this).find('[name="transaction[payment_new][value]"]').val() )
    {
      console.log(
        $('#li_transaction_field_payments_list tfoot .change .pit').html()
      );
      $(this).find('[name="transaction[payment_new][value]"]').val(
        parseFloat($('#li_transaction_field_payments_list tfoot tr.change .pit').html().replace(',','.'))
      );
    }
    return false;
  });
});
</script>
</form>

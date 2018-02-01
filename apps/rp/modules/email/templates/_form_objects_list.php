<?php $fieldName = strtolower($str['collection']).'_list'; ?>
<div class="sf_admin_form_row sf_admin_text sf_admin_form_field_<?php echo $fieldName ?>">
  <div class="label ui-helper-clearfix">
    <label for="email_<?php echo $fieldName ?>"><?php echo $str['title'] ?></label>
  </div>
  <div class="setbyfilter">
   
      <?php echo $form[$fieldName] ?>
   
  </div>
</div>

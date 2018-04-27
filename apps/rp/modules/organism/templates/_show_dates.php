<?php use_helper('Date') ?>
<span class="tdp-dates sf_admin_form_field_dates">
<span title="<?php echo __('Created at') ?>"><?php echo format_datetime($organism->created_at, 'f') ?> <?php echo __('by') ?> <strong><?php echo $organism->creator ?></strong></span>
<br/>
<span title="<?php echo __('Updated at') ?>"><?php echo format_datetime($organism->updated_at, 'f') ?> <?php echo __('by') ?> <strong><?php echo $organism->last_accessor ?></strong></span>
</span>

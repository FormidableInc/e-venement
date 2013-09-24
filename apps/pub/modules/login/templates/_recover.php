<?php foreach ( array('recovery_code', 'password', 'password_again') as $fieldname ): ?>
  <p class="<?php echo $fieldname ?>">
    <?php echo $form[$fieldname]->renderLabel() ?>
    <?php echo $form[$fieldname] ?>
    <span class="error"><?php if ( isset($errors[$fieldname]) ) echo __($errors[$fieldname]) ?></span>
  </p>
<?php endforeach ?>
  <p class="submit">
    <label></label>
    <input type="submit" value="<?php echo __('Send') ?>" name="submit" />
    <a href="<?php echo url_for('login/forgot') ?>" class="forgot"><?php echo __('You forgot your password?') ?></a>
  </p>

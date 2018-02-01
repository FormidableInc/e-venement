<?php
/**********************************************************************************
*
*	    This file is part of e-venement.
*
*    e-venement is free software; you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation; either version 2 of the License.
*
*    e-venement is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with e-venement; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
*    Copyright (c) 2006-2012 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2006-2012 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php $vel = sfConfig::get('app_tickets_vel', array()); ?>
<div id="contact">
  <?php if ( sfConfig::get('app_contact_picture', true) ): ?>
  <?php use_javascript('rp-picture-upload?'.date('Ymd')) ?>
  <div class="picture" data-contact-id="<?php echo $contact->id ?>">
    <h3><?php echo __('My picture') ?></h3>
    <div class="webcam small">
      <div class="live"></div>
      <button class="start" data-post-url="<?php echo url_for($sf_context->getModuleName().'/newPicture') ?>">
        <?php echo image_tag('camera.png', array('alt' => 'Webcam', 'size' => '25x25')) ?>
      </button>
    </div>
    <input class="file" type="file" name="file" data-post-url="<?php echo url_for($sf_context->getModuleName().'/newPicture') ?>" />
    <div class="current">
      <?php if ( $contact->picture_id ): ?>
        <?php echo $contact->getRawValue()->Picture->render(array('app' => sfContext::getInstance()->getConfiguration()->getApplication())) ?>
      <?php else: ?>
        <img src="" alt="" />
      <?php endif ?>
    </div>
  </div>
  <?php endif ?>
  <h2><?php echo $contact ?></h2>
	<p class="email">
	  <strong><?php echo __('Email') ?></strong>:
	  <?php $email = sfConfig::get('app_contact_professional', false) ? $sf_user->getTransaction()->Professional->contact_email : $contact->email ?>
	  <a href="mailto:<?php echo $email ?>"><?php echo $email ?></a>
	</p>
  <p><a class="actions edit" href="<?php echo url_for('contact/edit') ?>">
    <?php echo __('Update your contact information') ?>
  </a></p>
</div>
<div class="clear"></div>

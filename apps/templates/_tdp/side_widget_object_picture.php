<?php if ( !$object->getTable()->hasRelation('Picture') || $object->isNew() ) return; ?>
<div class="tdp-picture picture" data-contact-id="<?php echo $object->id ?>">
  <?php use_javascript('helper') ?>
  <?php use_javascript('photobooth') ?>
  <?php use_javascript('rp-picture-upload?'.date('Ymd')) ?>
  <div class="current">
    <?php if ( $object->picture_id ): ?>
      <a href="<?php echo url_for($sf_context->getModuleName().'/delPicture?id='.$object->id) ?>" target="_blank">x</a>
      <?php echo $object->Picture->getRawValue()->render() ?>
    <?php else: ?>
      <img alt="" src="" />
    <?php endif ?>
  </div>
  <div class="webcam small">
    <div class="live"></div>
    <button data-post-url="<?php echo url_for($sf_context->getModuleName().'/newPicture') ?>" class="start">
      <?php echo image_tag('camera.png') ?>
    </button>
  </div>
  <!--
  <a
    data-text-query="<?php echo __('Facebook ID') ?>"
    class="facebook"
    href="https://www.facebook.com/%%ID%%"
    target="_blank"
  ><?php echo image_tag('facebook.png') ?></a>
  -->
  <input class="file contact-picture" type="file" name="file" data-post-url="<?php echo url_for($sf_context->getModuleName().'/newPicture') ?>" />
</div>

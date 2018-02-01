<?php if ( $event->picture_id ): ?>
  <div class="event-pic">
    <?php echo $event->Picture->getRawValue()->render(array('app' => sfContext::getInstance()->getConfiguration()->getApplication())) ?>
  </div>
<?php endif ?>

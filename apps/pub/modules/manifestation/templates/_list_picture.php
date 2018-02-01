<?php echo $manifestation->Event->getRawValue()->Picture->render(array('app' => sfContext::getInstance()->getConfiguration()->getApplication())) ?>
<span
  data-manifestation-id="<?php echo $manifestation->id ?>"
  data-event-id="<?php echo $manifestation->event_id ?>"
></span>

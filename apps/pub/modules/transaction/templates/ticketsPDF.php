<?php
  if ( false && sfConfig::get('sf_web_debug', false) ) // because here the content-type is always set to PDF
  {
    echo get_partial('global/get_tickets_pdf', array('tickets_html' => $content));
    return;
  }
  
  $pdf = new liPDFPlugin();
  
  $options = sfConfig::get('project_tickets_pdf_options', array());
  foreach ($options as $key => $value) {
      $pdf->setOption($key, $value);
  }
  
  $pdf->setHtml(get_partial('global/get_tickets_pdf', array('tickets_html' => $content)));
  echo $pdf->getPDF();


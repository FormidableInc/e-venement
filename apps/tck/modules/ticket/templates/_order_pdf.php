<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="title" content="e-venement, Billet" />
    <title>e-venement, Billet</title>
    <link rel="shortcut icon" href="/images/logo-evenement.png" />
    <style><?php require(sfConfig::get('sf_web_dir').'/css/print-accounting.css') ?></style>
    <?php if ( file_exists($path = sfConfig::get('sf_web_dir').'/private/print-accounting.css') ): ?>
    <style><?php require($path) ?></style>
    <?php endif ?>
    <?php
      foreach ( ['/js/jquery.js', '/js/print-tickets.js'] as $js  => $opt )
      {
        if ( file_exists($file = $_SERVER['DOCUMENT_ROOT'].preg_replace('/\\?.*$/', '', $opt)) )
        {
          echo '<script type="text/javascript" data-orig="'.$js.'">'.file_get_contents($file).'</script>'."\n";
        }
      }
    ?>
  </head>
  <body class="pdf">
    <div id="content">
      <?php
        $data = array();
        foreach ( array('transaction', 'nocancel', 'tickets', 'products', 'invoice', 'totals', 'partial') as $var )
        if ( isset($$var) )
          $data[$var] = $$var;
      ?>
      <?php include_partial('order',$data) ?>
    </div>
  </body>
</html>

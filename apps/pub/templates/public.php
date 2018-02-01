<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" itemscope itemtype="http://schema.org/Event" prefix="og: http://ogp.me/ns#">
  <head>
    <?php $module_name = $sf_context->getModuleName() ?>
    <?php $sf_response->setTitle($sf_response->getTitle().sfConfig::get('app_informations_title'), false); ?>
    <?php include_http_metas() ?>
    <?php include_metas() ?>
    <?php include_title() ?>
    <meta property="og:url" content="<?php echo $sf_request->getUri() ?>" />
    <meta property="og:type" content="article" />
    <link rel="shortcut icon" href="<?php echo image_path('logo-evenement.png') ?>" />
    <?php include_stylesheets() ?>
    <?php include_component('layout', 'stylesheets') ?>
    <?php include_javascripts() ?>
    <script type="text/javascript" src="/js/translations/pub-<?php echo $sf_user->getCulture() ?>.js?<?php echo date('Ymd') ?>"></script>
  </head>
  <body class="<?php include_partial('global/body_classes') ?>">
    <?php include_component('layout', 'layoutSwitcher') ?>
    <div id="client-header"></div>
    <div id="content">
      <div class="text_config pub_message">
        <?php echo nl2br(pubConfiguration::getText('app_texts_pub_message')) ?>
      </div>
      <?php include_partial('global/oplog') ?>
      <?php echo $sf_content ?>
      <?php if ( sfConfig::get('app_social_media_display', false) ): ?>
        <?php include_partial('global/social_networks') ?>
      <?php endif ?>
    </div>
    <div id="client-footer">
    <?php if ( pubConfiguration::getText('app_texts_terms_conditions_url') ): ?>
      <a href="<?php echo url_for('cart/cgv');?>" target="_blank" title="<?php echo __('Terms & Conditions') ?>"><?php echo __('Terms & Conditions') ?></a>
    <?php endif ?>
    </div>
    <div id="client-infos"></div>
    <ul id="menu" class="first">
      <?php include_partial('global/public_choices') ?>
    </ul>
    <div id="footer">
      <?php include_partial('global/footer') ?>
      <?php include_partial('global/date') ?>
      <?php include_partial('global/cart_widget') ?>
    </div>
  </body>
</html>

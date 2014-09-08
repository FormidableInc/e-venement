<?php

/**
 * Picture
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Picture extends PluginPicture
{
  public function getHtmlTag(array $attributes = array())
  {
    if ( !$this->id )
      return '';
    
    sfApplicationConfiguration::getActive()->loadHelpers(array('CrossAppLink'));
    $attributes['src'] = cross_app_url_for(isset($attributes['app']) ? $attributes['app'] : 'default', 'picture/display?id='.$this->id);
    unset($attributes['app']);
    return $this->_getImageTag($attributes);
  }
  public function getHtmlTagInline(array $attributes = array())
  {
    $attributes['src'] = 'data:'.$this->type.';base64,'.$this->content;
    return $this->_getImageTag($attributes);
  }
  protected function _getImageTag(array $attributes = array())
  {
    if ( !isset($attributes['alt']) )
      $attributes['alt'] = $this->name;
    
    $tmp = array();
    foreach ( $attributes as $key => $value )
      $tmp[] = $key.'="'.$value.'"';
    $tag = '<img '.implode(' ',$tmp).' />';
    return $tag;
  }
  
  public function getContentStream()
  {
    return $this->rawGet('content');
  }
  public function getDecodedContent()
  {
    return base64_decode($this->getContent());
  }
  public function getContent()
  {
    if ( !is_resource($this->rawGet('content')) )
      return $this->rawGet('content');
    
    $data = stream_get_contents($this->rawGet('content'));
    rewind($this->rawGet('content'));
    return $data;
  }
}

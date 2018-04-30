<?php

/**
 * Addressable form.
 *
 * @package    e-venement
 * @subpackage form
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: sfDoctrineFormTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class AddressableForm extends BaseAddressableForm
{
  public function configure()
  {
    $this->widgetSchema['vcard_uid'] = new sfWidgetFormInputHidden;
    
    if ( is_null($this->object->vcard_uid) )
      unset($this->widgetSchema['vcard_uid']);
    
    if ( sfContext::hasInstance() && sfContext::getInstance()->getConfiguration()->getApplication() === 'rp' )
    foreach ( sfContext::getInstance()->getUser()->getGuardUser()->RpMandatoryFields as $option )
    {
      if ( isset($this->widgetSchema[$option->value]) )
        $this->validatorSchema[$option->value]->setOption('required', true);
    }
    
    if ( sfConfig::get('project_rp_street_validation', false) )
    {
      $this->validatorSchema['address'] = new liValidatorDoctrineGeoFrStreetBase(array(
        'form'      => $this,
        'required' => $this->validatorSchema['address']->getOption('required'),
      ));
    }
    
    if ( sfConfig::get('project_rp_list_country', false) )
    {
      $this->widgetSchema['country'] = new sfWidgetFormDoctrineChoice(array(
        'query' => Doctrine::getTable('Country')->createQuery('c')
          ->leftJoin("c.Translation ct WITH ct.lang = ?", sfContext::getInstance()->getUser()->getCulture())
          ->orderBy('ct.name'),
        'model' => 'Country', 
        'add_empty' => true,
        'key_method' => 'getName'
      ));
      
      $this->setDefault('country', 'FRANCE');  
    }
  }
}

<?php

/**
 * ContactEntryByContact form.
 *
 * @package    e-venement
 * @subpackage form
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: sfDoctrineFormTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class ContactEntryByContactForm extends BaseContactEntryForm
{
  public function configure()
  {
    if ( $this->getObject()->isNew() )
      $this->widgetSchema->setNameFormat('contact_entry_new[%s]');
    
    $this->widgetSchema['professional_id'] = new sfWidgetFormInputHidden;
    
    $this->widgetSchema   ['event_id'] = new sfWidgetFormDoctrineChoice(array(
      'model' => 'Event',
      'query' => $q = Doctrine::getTable('Event')->retrieveList()
        ->select('e.*')
        ->andWhere('g.workspace_id IN (SELECT gw.workspace_id FROM GroupWorkspace gw)'),
      'order_by' => array('translation.name', ''),
      'multiple' => true,
    ));
    $this->validatorSchema['event_id'] = new sfValidatorDoctrineChoice(array(
      'model' => 'Event',
      'query' => $q,
    ));
    
    unset(
      $this->widgetSchema   ['entry_id'],
      $this->validatorSchema['entry_id'],
      $this->widgetSchema   ['comment1'],
      $this->validatorSchema['comment1'],
      $this->widgetSchema   ['comment2'],
      $this->validatorSchema['comment2'],
      $this->widgetSchema   ['transaction_id'],
      $this->validatorSchema['transaction_id'],
      $this->widgetSchema   ['confirmed'],
      $this->validatorSchema['confirmed']
    );
    
    $this->enableCSRFProtection();
  }
  
  public function restoreProfessionalId()
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('CrossAppLink'));
    $this->widgetSchema['professional_id'] = new liWidgetFormDoctrineJQueryAutocompleter(array(
      'model' => 'Professional',
      'url'   => cross_app_url_for('rp', 'professional/ajax'),
    ));
    return $this;
  }
  
  public function setDefault($name, $default)
  {
    if ( $name == 'professional_id' )
    {
      $q = Doctrine_Query::create()
        ->from('Event e1')
        ->select('e1.id')
        ->leftJoin('e1.Manifestations m1')
        ->leftJoin('m1.ManifestationEntries mentry')
        ->leftJoin('mentry.Entry entry')
        ->leftJoin('entry.ContactEntries ce')
        ->andWhere('ce.professional_id = ?', $default)
      ;
      $r = Doctrine_Query::create()
        ->from('Event e2')
        ->select('e2.id')
        ->leftJoin('e2.Entries ee2')
        ->leftJoin('ee2.ContactEntries ce2')
        ->andWhere('ce2.professional_id = ?', $default)
      ;
        
      $this->widgetSchema['event_id']->getOption('query')
        ->andWhere("e.id NOT IN ($q) AND e.id NOT IN ($r)", array($default, $default));
    }
    
    return parent::setDefault($name, $default);
  }
  
  public function doSave($con = null)
  {
    $q = Doctrine::getTable('Entry')->createQuery('e')
      ->leftJoin('e.Event ev')
      ->leftJoin('ev.Manifestations m')
      ->leftJoin('m.Gauges g')
      ->leftJoin('e.ManifestationEntries me')
      ->andWhere('ev.id = ?',$this->values['event_id'])
      ->andWhere('g.workspace_id IN (SELECT gw.workspace_id FROM GroupWorkspace gw)');
    if ( sfContext::hasInstance() )
    {
      $sf_user = sfContext::getInstance()->getUser();
      $q->andWhereIn('g.workspace_id', array_keys($sf_user->getWorkspacesCredentials()))
        ->andWhereIn('ev.meta_event_id', array_keys($sf_user->getMetaEventsCredentials()))
      ;
    }
    $entry = $q->fetchOne();
    if ( !$entry )
    {
      $entry = new Entry;
      $entry->Event = Doctrine::getTable('Event')->retrieveList()
        ->andWhere('g.workspace_id IN (SELECT gw.workspace_id FROM GroupWorkspace gw)')
        ->andWhere('e.id = ?', $this->values['event_id'])
        ->fetchOne();
    }
    
    if ( $entry->ManifestationEntries->count() == 0 && $entry->Event->Manifestations->count() > 0 )
    foreach ( $entry->Event->Manifestations as $manif )
    {
      $me = new ManifestationEntry;
      $me->Manifestation = $manif;
      $entry->ManifestationEntries[] = $me;
    }
    
    $this->object->Entry = $entry;
    $this->object->confirmed = false;
    parent::doSave($con);
  }
}

<?php

require_once dirname(__FILE__).'/../lib/professional_fullGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/professional_fullGeneratorHelper.class.php';

/**
 * professional_full actions.
 *
 * @package    e-venement
 * @subpackage professional_full
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class professional_fullActions extends autoProfessional_fullActions
{
  public function executeNewContact(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers('CrossAppLink');
    $this->redirect(cross_app_url_for('rp', 'contact/new'));
  }
  public function executeIndex(sfWebRequest $request)
  {
    $this->redirect('professional/index');
  }
  
  public function executeEdit(sfWebRequest $request)
  {
    parent::executeEdit($request);
    
    $q = Doctrine::getTable('Professional')->createQuery('p')
      ->andWhere('p.id = ?',$request->getParameter('id'));
    Doctrine::getTable('Professional')->doSelectOnlyGrp($q);
    $this->professional = $q->fetchOne();
    
    if ( !$this->professional )
    {
      $this->getContext()->getConfiguration()->loadHelpers('I18N');
      $this->getUser()->setFlash('notice', __('This entry is now empty. Create a new one if needed.'));
      $this->redirect('professional_full/new');
    }
  }
  public function executeNew(sfWebRequest $request)
  {
    $this->form = new ContactEntryByContactForm;
    $this->form->restoreProfessionalId();
    $this->professional = $this->form->getObject();
  }
  public function executeCreate(sfWebRequest $request)
  {
    $data = $request->getParameter('contact_entry_new');
    $uri = false;
    
    foreach ( $data['event_id'] as $event_id ) {
        $this->form = new ContactEntryByContactForm;
        $this->form->restoreProfessionalId();
        $tmp = $data;
        $tmp['event_id'] = $event_id;
        $this->form->bind($tmp);
        if ( $this->form->isValid() )
        {
          $this->form->save();
          $this->getUser()->setFlash('success', 'The item was created successfully.');
          $uri = 'professional_full/edit?id='.$this->form->getObject()->Professional->id;
        }
    }
    
    if ( $uri ) {
        $this->redirect($uri);
    }
    
    $this->getUser()->setFlash('error', 'The item has not been saved due to some errors.');
    $this->redirect('professional_full/new');
  }
  public function executeUpdate(sfWebRequest $request)
  { throw new liEvenementException('This action is not implemented.'); }
  public function executeDelete(sfWebRequest $request)
  { throw new liEvenementException('This action is not implemented.'); }
  public function executeBatchDelete(sfWebRequest $request)
  { throw new liEvenementException('This action is not implemented.'); }
}

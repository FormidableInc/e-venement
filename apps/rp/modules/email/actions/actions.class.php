<?php

require_once dirname(__FILE__).'/../lib/emailGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/emailGeneratorHelper.class.php';

/**
 * email actions.
 *
 * @package    e-venement
 * @subpackage email
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class emailActions extends autoEmailActions
{
  public function executeNewFromTemplate(sfWebRequest $request)
  {
    $this->redirect('@email_template');
  }
  public function executeSaveTemplate(sfWebRequest $request)
  {
    $template = new EmailTemplate;
    $template->name = $request->getParameter('name');
    $template->content = $request->getParameter('content');
    $template->created_at = date('Y-m-d H:i:s');
    $template->save();
    
    return sfView::NONE;
  }
  
  public function executeUpload(sfWebRequest $request)
  {
    $this->email = $this->getRoute()->getObject();
  }
  public function executeAttach(sfWebRequest $request)
  {
    $email = $request->getParameter('email');
    $this->email = Doctrine::getTable('email')->find($email['id']);
    
    $arr = $request->getFiles();
    
    // if the storage *does not need* to be done by the DB
    if ( count($this->email->getTable()->getConnection()->getManager()->getConnections()) == 1 )
    {
      $file = new liFileAttachment($arr['attachment']['name'],$arr['attachment']['type'],$arr['attachment']['tmp_name'],$arr['attachment']['size'],sfConfig::get('sf_upload_dir'));
      $file->setEmail($this->email);
      $file->save(sfConfig::get('sf_upload_dir').'/'.$file->generateFilename());
    }
    // if the storage *needs* to be done by the DB
    else
    {
      $file = new Picture;
      $file->name = 'db:'.$arr['attachment']['tmp_name'];
      $file->content = $tmp = base64_encode(file_get_contents($arr['attachment']['tmp_name']));
      $file->type = $arr['attachment']['type'];
      $file->save();
      
      $attachment = new Attachment;
      $attachment->original_name = $arr['attachment']['name'];
      $attachment->mime_type = $arr['attachment']['type'];
      $attachment->size = $arr['attachment']['size'];
      $attachment->filename = 'db:'.$arr['attachment']['tmp_name'];
      $this->email->Attachments[] = $attachment;
      $attachment->save();
    }
    
    $this->getUser()->setFlash('notice','File attached.');
    $this->redirect('email/edit?id='.$this->email->id);
    return sfView::NONE;
  }
  public function executeDeleteAttachment(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    
    $q = new Doctrine_Query;
    $attachment = $q->from('Attachment a')
      ->andWhere('a.id = ?', $request->getParameter('attachment_id'))
      ->andWhere('a.email_id = ?', $request->getParameter('id'))
      ->fetchOne();
    unlink(sfConfig::get('sf_upload_dir').'/'.$attachment->filename);
    $attachment->delete();
    
    $this->getUser()->setFlash('notice', __('The item was deleted successfully.'));
    $this->redirect('email/edit?id='.$request->getParameter('id'));
  }
  public function executeIntegrateAttachment(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    
    $q = new Doctrine_Query;
    $attachment = $q->from('Attachment a')
      ->andWhere('a.id = ?',$request->getParameter('attachment_id'))
      ->andWhere('a.email_id = ?', $request->getParameter('id'))
      ->leftJoin('a.Email e')
      ->fetchOne();
    if (!( $attachment && preg_match('!^image\/!', $attachment->mime_type) === 1 ))
    {
      $this->getUser()->setFlash('error', __('The given attachment is not found or does not match the prequisites'));
      $this->redirect('email/edit?id='.$request->getParameter('id'));
    }
    
    $img = file_get_contents($path = sfConfig::get('sf_upload_dir').'/'.$attachment->filename);
    $attachment->Email->addImageToContent($img, $attachment->mime_type)->save();
    
    unlink(sfConfig::get('sf_upload_dir').'/'.$attachment->filename);
    $attachment->delete();
    
    $this->getUser()->setFlash('notice', __('Image integrated properly into the content of your email.'));
    $this->redirect('email/edit?id='.$request->getParameter('id'));
  }
  
  public function executeContent(sfWebRequest $request)
  {
    sfConfig::set('sf_escaping_strategy', false);
    sfConfig::set('sf_web_debug', false);
    $this->object = $this->getRoute()->getObject();
    $this->column = 'content';
  }
  public function executeCopy(sfWebRequest $request)
  {
    $this->email = $this->getRoute()->getObject()->copy();
    foreach ( array('Contacts', 'Professionals', 'Organisms') as $relation )
    foreach ( $this->getRoute()->getObject()->$relation as $obj )
    {
      $rel = $this->email->$relation;
      $rel[] = $obj;
    }
    $this->email->sent = false;
    
    foreach ( $this->getRoute()->getObject()->Attachments as $att )
      $this->email->Attachments[] = $att->copy();
    
    $this->email->save();
    $this->redirect('email/edit?id='.$this->email->id);
  }
  
  public function executeEdit(sfWebRequest $request)
  {
    $r = parent::executeEdit($request);
    
    // if object has been sent, cannot be modified again
    if ( $this->email->sent )
    //  $this->form->removeAlreadyKnownReceipientsList();
   // else
      $this->setTemplate('show');
    
    return $r;
  }
  public function executeUpdate(sfWebRequest $request)
  {
    $email = $request->getParameter('email');
    $this->email = $this->getRoute()->getObject();
    $this->form = $this->configuration->getForm($this->email);
    //$this->form->removeAlreadyKnownReceipientsList();
        
    if ($request->getParameter('email-send-button')) {
      $this->email->isATest(false);
      
      if ( empty($email['organisms_list']) 
        && empty($email['contacts_list']) 
        && empty($email['professionals_list'])
        && $this->form->getObject()->Contacts->count() == 0 
        && $this->form->getObject()->Organisms->count() == 0 
        && $this->form->getObject()->Professionals->count() == 0 ) 
      {
        $this->getUser()->setFlash('error',__('No receiver address specififed'));
        $this->redirect('email/edit?id='.$this->email->id);
      }      
    }
    
    if ( $this->email->sent )
    {
      $this->getUser()->setFlash('error',"You can't modify an email already sent !");
      $this->redirect(url_for('email/show?id='.$this->email->id));
    }
    
    $this->saveEmail($request);
    
    $this->setTemplate('edit');
  }
  public function executeCreate(sfWebRequest $request)
  {
    $this->executeNew($request);
    //$this->form = $this->configuration->getForm();
    $this->email = $this->form->getObject();
    $this->email->isATest(true);
    
    if ( $this->getUser() instanceof sfGuardSecurityUser )
      $this->email->sf_guard_user_id = $this->getUser()->getId();
    
    $this->saveEmail($request);
    
    $this->setTemplate('new');
  }
  public function executeNew(sfWebRequest $request)
  {
    $r = parent::executeNew($request);
    $edit = false;
    
    // CONTACTS
    $criterias = $this->getUser()->getAttribute('contact.filters', $this->configuration->getFilterDefaults(), 'admin_module');
    if ( !is_array($criterias) )
      $criterias = array();
    
    foreach ( $criterias as $name => $criteria )
    if ( !$criteria || !is_string($criteria) && !(is_array($criteria) && implode('',$criteria)) )
      unset($criterias[$name]);
    
    if ( $criterias )
    {
      // standard filtering
      $filters = new ContactFormFilter($criterias);
      foreach ( $filters->buildQuery($criterias)->execute() as $contact )
      {
        // check if it's in a group because of a link to an organism or not
        $groups_pro = array();
        $group_pro = false;
        if ( isset($criterias['groups_list']) && $criterias['groups_list'] )
        {
          foreach ( $contact->Professionals as $pro )
          foreach ( $pro->Groups as $group )
            $groups_pro[$group->id] = $group;
          foreach ( $criterias['groups_list'] as $grpid )
          if ( $group_pro = isset($groups_pro[$grpid]) )
              break;
        }
        
        if ( $contact->Professionals->count() > 0
          && ($filters->showProfessionalData() || $group_pro) )
        foreach ( $contact->Professionals as $pro )
          $this->form->getObject()->Professionals[] = $pro;
        else
          $this->form->getObject()->Contacts[] = $contact;
      }
      $edit = true;
    }
    
    // ORGANISMS
    $criterias = $this->getUser()->getAttribute('organism.filters', $this->configuration->getFilterDefaults(), 'admin_module');
    if ( !is_array($criterias) )
      $criterias = array();
    
    foreach ( $criterias as $name => $criteria )
    if ( !$criteria || !is_string($criteria) && !(is_array($criteria) && implode('',$criteria)) )
      unset($criterias[$name]);
    
    if ( $criterias )
    {
      // standard filtering
      $filters = new OrganismFormFilter($criterias);
      foreach ( $filters->buildQuery($criterias)->execute() as $organism )
        $this->form->getObject()->Organisms[] = $organism;
      $edit = true;
    }
    
    // templating
    if ( intval($request->getParameter('template')).'' === ''.$request->getParameter('template')
      && ($template = Doctrine::getTable('EmailTemplate')->find($request->getParameter('template'))) )
    {
      $this->form->getObject()->content = $template->content;
      $this->form->setDefault('content', $template->content);
    }
    
    if ( $edit )
    {
      $this->form->getObject()->field_from = $this->getUser()->getGuardUser()->getEmailAddress();
      $this->form->getObject()->field_subject = '*****';
      if ( !$this->form->getObject()->content )
        $this->form->getObject()->content = '*****';
      $this->form->getObject()->save();
      
      $this->getUser()->setFlash('notice', __('Your email has been temporary recorded. Please be careful, modify its subject and its content before sending...'));
      $this->redirect('email/edit?id='.$this->form->getObject()->id);
    }
    
    //$this->form->setDefault('contacts_list',$contacts_list);
    //$this->form->setDefault('professionals_list',$professionals_list);
    //$this->form->setDefault('organisms_list',$organisms_list);
    //$this->form->removeAlreadyKnownReceipientsList();
    
    return $r;
  }
  
  protected function saveEmail(sfWebRequest $request) {
    
    $email = $request->getParameter('email');
    
    if ( $request->getParameter('email-test-button') && empty($email['test_address']) ) {
      $this->form->getValidator('test_address')->setOption('required', true);
    }
    
    // loading templates    
    if ( $email['load'] )
    {
      $email['content'] = file_get_contents($email['load']);
      unset($email['load'],$email['test_address']);
    }
    
    $this->email->setMailer($this->getMailer());
    $this->email->test_address = $email['test_address'];
    
    $request->setParameter('email', $email);
    try {
      $this->processForm($request, $this->form);
    }
    catch(Swift_TransportException $e)
    {
      $this->getUser()->setFlash('error','An error occured sending the email (smtp unreachable)');
      $this->redirect('email/edit?id='.$this->email->id);
    }
    catch(Swift_RfcComplianceException $e)
    {
      $this->getUser()->setFlash('error','An error occured sending the email ('.$e->getMessage().')');
      $this->redirect('email/edit?id='.$this->email->id);
    }
    
  }
}

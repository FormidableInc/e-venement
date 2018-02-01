<?php
/**********************************************************************************
*
*	    This file is part of e-venement.
*
*    e-venement is free software; you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation; either version 2 of the License.
*
*    e-venement is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with e-venement; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
*    Copyright (c) 2006-2011 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2011 Ayoub HIDRI <ayoub.hidri AT gmail.com>
*    Copyright (c) 2006-2011 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php

require_once dirname(__FILE__).'/../lib/contactGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/contactGeneratorHelper.class.php';

/**
 * contact actions.
 *
 * @package    e-venement
 * @subpackage contact
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class contactActions extends autoContactActions
{
  private $force_classic_template_dir = false;

  public function executeDuplicate(sfWebRequest $request)
  {
    $this->executeShow($request);
    
    $contact = new Contact;
    $contact->name = $this->contact->name;
    $contact->address = $this->contact->address;
    $contact->postalcode = $this->contact->postalcode;
    $contact->city = $this->contact->city;
    $contact->country = $this->contact->country;
    foreach ( $this->contact->Phonenumbers as $pn )
      $contact->Phonenumbers[] = $pn->copy();
    
    $contact->save();
    $this->getUser()->setFlash('notice', 'The item was created successfully.');
    $this->redirect('contact/edit?id='.$contact->id);
  }
  public function executePrepareImport(sfWebRequest $request)
  {
    $this->useClassicTemplateDir(true);
  }
  public function executeImport(sfWebRequest $request)
  {
    return require(__DIR__.'/import.php');
  }

  public function executeDelPicture(sfWebRequest $request)
  {
    $this->executeShow($request);
    $this->contact->Picture->delete();
    return sfView::NONE;
  }
  public function executeNewPictureDistant(sfWebRequest $request)
  {
    if (!( $request->getParameter('id').'' === ''.intval($request->getParameter('id')) && $url = $request->getParameter('url') ))
      throw new liEvenementException('Needs more informations to retrieve the expected data');
    if ( !$this->contact = Doctrine::getTable('Contact')->find($request->getParameter('id')) )
      throw new liEvenementException('No contact found for picture update with the id '.$request->getParameter('id'));

    file_put_contents(sfConfig::get('sf_app_cache_dir').'/fb-test.html', file_get_contents($url));

    return sfView::NONE;
  }
  public function executeNewPicture(sfWebRequest $request)
  {
    $this->executeShow($request);
    if ( $request->getParameter('image', false) )
    {
      if ( !$this->contact->Picture->isNew() )
        $this->contact->Picture->delete();
      $this->contact->Picture = new Picture;

      $this->contact->Picture->content = $request->getParameter('image');
      $this->contact->Picture->name = 'contact-'.$this->contact->id.'-'.date('YmdHis').'.img';
      $this->contact->Picture->type = $request->getParameter('type');
      $this->contact->save();
    }
    return sfView::NONE;
  }

  public function executeError404(sfWebRequest $request)
  {
    $this->useClassicTemplateDir(true);
  }
  public function postExecute()
  {
    $this->addExtraRequirements();
    if ( !$this->useClassicTemplateDir() )
      $this->getContext()->getConfiguration()->changeTemplatesDir($this);
    return parent::postExecute();
  }
  protected function useClassicTemplateDir($bool = NULL)
  {
    if ( is_null($bool) )
      return $this->force_classic_template_dir;

    $this->force_classic_template_dir = $bool;
    return $this;
  }
  protected function addExtraRequirements()
  {
    if ( sfConfig::get('app_options_design') == 'tdp' && sfConfig::get(sfConfig::get('app_options_design').'_active',false) )
    {
      if ( !isset($this->hasFilters) )
        $this->hasFilters = $this->getUser()->getAttribute('contact.filters', $this->configuration->getFilterDefaults(), 'admin_module');
      if ( !isset($this->filters) )
        $this->filters = $this->configuration->getFilterForm($this->getFilters());
      if ( in_array($this->getActionName(), array('edit','new','show','create','update','delete')) )
        $this->setTemplate('edit');
      if ( in_array($this->getActionName(), array('duplicates')) )
        $this->setTemplate('index');
    }
  }

  public function executeSendPasswords(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers('I18N');

    $limit = 100;
    $q = $this->buildQuery()
      ->limit($limit)
      ->offset($offset = $request->getParameter('offset',0))
      ->select('c.*, p.*, o.*');
    
    $a = $q->getRootAlias();
    $i = 0;
    $errors = array();
    foreach ( $q->execute() as $contact )
    {
      $i++;
      echo "\n$i: ".memory_get_usage(true);
      // new password
      $letters = 'abcefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
      if ( !trim($contact->password) )
      {
        $contact->password = substr(str_shuffle($letters), 0, sfConfig::get('app_contact_auto_password_size', 6));
        $contact->save();
      }

      $params = OptionCsvForm::getDBOptions();
      if ( in_array('tunnel',$params['option']) ) // prefer professionals
      {
        foreach ( $contact->Professionals as $pro )
        if ( $pro->contact_email ) try
        {
          $this->sendPassword($pro);
        }
        catch ( Swift_RfcComplianceException $e )
        { $errors[] = $pro->contact_email; }
      }
      elseif ( $contact->email )
        $this->sendPassword($contact);
    }

    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    $this->getUser()->setFlash('notice', __('Your email have been sent correctly.'));
    if ( count($errors) > 0 )
    {
      $this->getUser()->setFlash('error', $errors = implode(', ', $errors));
      error_log('Passwords sent with errors: '.$errors);
    }

    if ( $this->buildQuery()->count() > $offset+$limit )
      $this->redirect('contact/sendPasswords?offset='.($offset+$limit));
    else
    {
      $this->getUser()->setFlash('success', 'Ok');
      $this->redirect('email/index');
    }
  }
  protected function sendPassword($obj)
  {
    if (!( $obj instanceof Contact ? $obj->email : $obj->contact_email ))
      return false;

    // format content
    $url = sfConfig::get('app_contact_password_reminder_url', str_replace('https', 'http', public_path('/pub.php',true)));
    $content  = '';
    $content .= __('To login to your private space, go to %%url%%', array('%%url%%' => '<a href="'.$url.'">'.$url.'</a>'));
    $content .= "<br/>";
    $content .= "<br/>";
    $content .= __('Login: %%login%%', array('%%login%%' => $obj instanceof Contact ? $obj->email : $obj->contact_email));
    $content .= "<br/>";
    $content .= __('Password: %%password%%', array('%%password%%' => $obj instanceof Contact ? $obj->password : $obj->Contact->password));
    $content .= "<br/>";
    $content .= "<br/>";
    $content .= __('Thanks for your attention');
    $content .= "<br/>";
    $content .= "<br/>";
    $content .= '-- ';
    $content .= "<br/>";
    $about = sfConfig::get('project_about_client');
    $content .= $about['name'];

    // send password
    $email = new Email;
    $email->not_a_test = true;
    $email->content = $content;
    $email->field_from = $this->getUser()->getGuardUser()->email_address;
    $email->field_subject = __('Password reminder for your private space at %%name%%', array('%%name%%' => $about['name']));

    if ( $obj instanceof Contact )
      $email->Contacts[] = $obj;
    else
      $email->Professionals[] = $obj;
    return $email->save();
  }

  public function executeBatch(sfWebRequest $request)
  {
    $request->checkCSRFProtection();

    $ids = $request->getParameter('ids',array());
    $pro_ids = $request->getParameter('professional_ids',array());

    if (!( $ids || $pro_ids ))
    {
      $this->getUser()->setFlash('error', 'You must at least select one item.');

      $this->redirect('@contact');
    }

    if (!$action = $request->getParameter('batch_action'))
    {
      $this->getUser()->setFlash('error', 'You must select an action to execute on the selected items.');

      $this->redirect('@contact');
    }

    if (!method_exists($this, $method = 'execute'.ucfirst($action)))
    {
      throw new InvalidArgumentException(sprintf('You must create a "%s" method for action "%s"', $method, $action));
    }

    if (!$this->getUser()->hasCredential($this->configuration->getCredentials($action)))
    {
      $this->forward(sfConfig::get('sf_secure_module'), sfConfig::get('sf_secure_action'));
    }

    $contacts = new sfValidatorDoctrineChoice(array('model' => 'Contact', 'multiple' => true));
    $professionals = new sfValidatorDoctrineChoice(array('model' => 'Professional', 'multiple' => true));
    try
    {
      // validate ids
      if ( $ids )
        $request->setParameter('ids',$contacts->clean($ids));
      if ( $pro_ids )
        $request->setParameter('professional_ids',$professionals->clean($pro_ids));

      // execute batch
      $this->$method($request);
    }
    catch (sfValidatorError $e)
    {
      $this->getUser()->setFlash('error', 'A problem occured when deleting the selected items as some items do not exist anymore.');
    }

    $this->redirect('@contact');
  }

  public function executeBatchAddToGroup(sfWebRequest $request)
  {
    $request->checkCSRFProtection();

    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    $filters = $request->getParameter($this->getModuleName().'_filters');

    try {
      $validator = new sfValidatorDoctrineChoice(array('model' => 'Contact', 'multiple' => true, 'required' => false));
      $ids = $validator->clean($request->getParameter('ids'));
      $validator = new sfValidatorDoctrineChoice(array('model' => 'Professional', 'multiple' => true, 'required' => false));
      $pro_ids = $validator->clean($request->getParameter('professional_ids'));
      $validator = new sfValidatorDoctrineChoice(array('model' => 'Group', 'multiple' => true));
      $groups = $request->getParameter('contact_filters');
      $groups = $validator->clean(isset($groups['groups_list'])
        ? $groups['groups_list']
        : $filters['groups_list']
      );
    }
    catch (sfValidatorError $e)
    {
      error_log('contact/batchAddToGroup: '.$e->getMessage());
      $this->getUser()->setFlash('error', 'A problem occured when adding the selected items as some items do not exist anymore.');
      return $this->redirect('@contact');
    }

    // contacts
    if ( count($ids) > 0 )
    foreach ( $ids as $contact_id )
    foreach ( $groups as $group_id )
    {
      $gc = new GroupContact;
      $gc->contact_id = $contact_id;
      $gc->group_id = $group_id;

      try { $gc->save(); }
      catch(Doctrine_Exception $e) { error_log('contact/batchAddToGroup: '.$e->getMessage()); }
    }

    // professionals
    if ( count($pro_ids) > 0 )
    foreach ( $pro_ids as $pro_id )
    foreach ( $groups as $group_id )
    if ( intval($group_id).'' === $group_id.'' )
    {
      $gc = new GroupProfessional();
      $gc->professional_id = $pro_id;
      $gc->group_id = $group_id;

      try { $gc->save(); }
      catch(Doctrine_Exception $e) {}
    }

    $this->getUser()->setFlash('notice',__('The chosen contacts and professionals have been added to the selected groups.'));
    $this->redirect('@contact');
  }
  public function executeBatchDelete(sfWebRequest $request)
  {
    $this->dispatcher->notify(new sfEvent($this, 'admin.delete_objects', array(
      'objects' => Doctrine::getTable('Contact')->createQuery('c')
        ->andWhereIn('c.id',$request->getParameter('ids'))
        ->select('c.*')
        ->execute(),
    )));
    return parent::executeBatchDelete($request);
  }

  public function executeArchives(sfWebRequest $request)
  {
    die('deprecated');
    $this->executeEdit($request);
    $this->setTemplate('edit');
    $this->contact->Professionals = $this->contact->ProfessionalArchives;
  }
  public function executeVersion(sfWebRequest $request)
  {
    $this->executeShow($request);

    if ( !($v = $request->getParameter('v',false)) )
      $v = $this->contact->version > 1 ? $this->contact->version - 1 : 1;

    if ( $v < 1 )
      $v = 1;

    $request->setParameter('v',$v);

    if (!( intval($v).'' == ''.$v && $this->contact->getSearchedVersion($v) ))
    {
      $this->getContext()->getConfiguration()->loadHelpers('I18N');
      $this->getUser()->setFlash('error', __('You have requested the version #%%v%% that does not exist.', array('%%v%%' => $v)));
      $this->redirect('contact/show?id='.$this->contact->id);
    }

    $this->object = $this->contact;
  }
  public function executeShow(sfWebRequest $request)
  {
    //$this->contact = Doctrine::getTable('Contact')->findWithTickets($request->getParameter('id'));
    $this->contact = Doctrine::getTable('Contact')->findOneById(intval($request->getParameter('id')));
    $this->forward404Unless($this->contact instanceof Contact);
    $this->form = $this->configuration->getForm($this->contact);
  }
  public function executeEdit(sfWebRequest $request)
  {
    $this->executeShow($request);

    if ( (sfConfig::get('app_options_design',false) != 'tdp' || sfConfig::get('app_options_design',false) && !sfConfig::get(sfConfig::get('app_options_design').'_active',false) )
      && !$this->getUser()->hasCredential('pr-contact-edit') )
      $this->setTemplate('show');
  }
  public function executeEvents(sfWebRequest $request)
  {
    return require(__DIR__.'/events.php');
  }

  public function executeNew(sfWebRequest $request)
  {
    parent::executeNew($request);
    $this->object = $this->form->getObject();
    $this->form->getWidget('name')->setOption('default',$request->getParameter('name'));
  }
  public function executeCreate(sfWebRequest $request)
  {
    // hack for the title to be recorded properly
    $params = $request->getParameter('contact');
    if ( !isset($params['title']) && $autocomplete = $request->getParameter('autocomplete_contact') )
      $params['title'] = $autocomplete['title'];
    $request->setParameter('contact',$params);

    parent::executeCreate($request);

    if ( $this->form->isValid() && $params['phone_number'] )
    {
      $pn = new ContactPhonenumber();
      $pn->name = $params['phone_type'];
      $pn->number = $params['phone_number'];
      $pn->contact_id = $this->contact->id;
      $pn->save();
    }
  }

  public function executeSearchIndexing(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers('I18N');

    $table = Doctrine_Core::getTable('Contact');
    $table->getTemplate('Doctrine_Template_Searchable')->getPlugin()
      ->setOption('analyzer', new MySearchAnalyzer());
    $table->batchUpdateIndex($nb = 1500);

    $this->getUser()->setFlash('notice',__('%nb% records have been indexed',array('%nb%' => $nb)));
    $this->executeIndex($request);
    $this->setTemplate('index');
  }

  public function executeLabels(sfWebRequest $request)
  {
    require(dirname(__FILE__).'/labels.php');
  }
  public function executeDuplicates(sfWebRequest $request)
  {
    self::executeIndex($request);

    $this->pager->setPage($request->getParameter('page') ? $request->getParameter('page') : 1);
    $q = new Doctrine_RawSql();
    $q->from('Contact c')
      ->leftJoin('(SELECT lower(name) as name, lower(firstname) as firstname, count(*) AS nb FROM contact WHERE confirmed = true GROUP BY lower(name), lower(firstname) ORDER BY lower(name), lower(firstname)) AS c2 ON c2.firstname ILIKE c.firstname AND c2.name ILIKE c.name')
      ->where('c2.nb > 1')
      ->andWhere('c.confirmed = ?', true)
      ->orderBy('lower(c.name), lower(c.firstname), c.id')
      ->addComponent('c','Contact')
      ->addComponent('c2','Contact');
    $this->pager->setQuery($q);

    $this->pager->init();
  }
  public function executeBatchMerge(sfWebRequest $request)
  {
    require(dirname(__FILE__).'/batch-merge.php');
  }
  public function executeBatchRemoveFromFilters(sfWebRequest $request)
  {
    require(dirname(__FILE__).'/batch-remove-from-filters.php');
  }

  public function executeSearch(sfWebRequest $request)
  {
    self::executeIndex($request);
    $this->quickest = true;
    $table = Doctrine_Core::getTable('Contact');

    if ( intval($request->getParameter('s')) > 0 )
    {
      $value = $request->getParameter('s');
      try { $value = liBarcode::decode_ean($value); }
      catch ( sfException $e )
      { $value = intval($value); }

      $this->pager->setQuery($table->createQuery('c')->leftJoin('c.MemberCards mc')->andWhere('c.id = ?',$value));
    }
    else
    {
      $search = $this->sanitizeSearch($request->getParameter('s'));
      $transliterate = sfConfig::get('software_internals_transliterate',array());

      $this->pager->setQuery($table->search($search.'*',$this->pager->getQuery())->orWhere('o.name ILIKE ?',$search.'%'));
    }

    $this->pager->setPage($request->getParameter('page') ? $request->getParameter('page') : 1);
    $this->pager->init();

    $this->setTemplate('index');
  }
  public function executeGroupList(sfWebRequest $request)
  {
    require(dirname(__FILE__).'/group-list.php');
  }
  public function executeEmailList(sfWebRequest $request)
  {
    if ( !$request->getParameter('id') )
      $this->forward('contact','index');

    $this->group_id = $this->email_id = $request->getParameter('id');
    $q = Doctrine::getTable('Contact')->createQueryByEmailId($this->email_id);

    $this->pager = $this->configuration->getPager('Contact');
    $this->pager->setMaxPerPage(15);
    $this->pager->setQuery($q);
    $this->pager->setPage($request->getParameter('page') ? $request->getParameter('page') : 1);
    $this->pager->init();
  }

  public function executeFilters(sfWebRequest $request)
  {
    $this->executeIndex($request);

    // optimizations
    $ws = $this->filters->getWidgetSchema();
    $ws['groups_list'] = $ws['not_groups_list'];
  }
  public function executeSideBar(sfWebRequest $request)
  {
    $this->filters = false;  // a trick that avoids until 5 sec of processing #1/2
    $cacher = liCacher::create('contact/sideBar?sf_guard_user_id='.$this->getUser()->getId())
      ->setDomain('rp-index');
    if ( !$cacher->requiresRefresh($request) )
    if ( ($this->cache = $cacher->useCache()) !== false )
      return 'Success';

    unset($this->filters);  // trick #2/2
    $this->executeIndex($request);
    $this->cache = $this->getPartial('global/tdp/side_widget', array(
      'filters' => $this->filters,
    ));

    $cacher
      ->setData($this->cache)
      ->writeData();
  }
  public function executeIndex(sfWebRequest $request)
  {
    if ( $organism_id = intval($request->getParameter('organism_id')) )
    {
      $this->setFilters(array('organism_id' => $organism_id));
      $this->redirect('contact/index');
    }
    parent::executeIndex($request);
    if ( !$this->sort[0] )
    {
      $this->sort = array('name','');
      $this->pager->getQuery()->orderby('name');
    }
    /*
    if ( $organism_id = intval($request->getParameter('organism_id')) )
    {
      $this->pager->getQuery()->andWhere('o.id = ?', $organism_id);
      echo $this->pager->getCountQuery()->andWhere('o.id = ?', $organism_id);
    }
    */
  }
  public function executeAjax(sfWebRequest $request)
  {
    //$this->getResponse()->setContentType('application/json');
    $this->useClassicTemplateDir(true);
    if ( $request->hasParameter('debug') && $this->getContext()->getConfiguration()->getEnvironment() == 'dev' )
    {
      $this->getResponse()->setContentType('text/html');
      sfConfig::set('sf_debug',true);
      $this->setLayout('layout');
    }
    else
    {
      sfConfig::set('sf_debug',false);
      sfConfig::set('sf_escaping_strategy', false);
    }

    $charset = sfConfig::get('software_internals_charset');
    $search  = iconv($charset['db'],$charset['ascii'],$request->getParameter('q'));

    $unconfirmed = (int) $request->getParameter('unconfirmed', 0);
    $q = Doctrine::getTable('Contact')
      ->createQuery('c', $unconfirmed)
      ->orderBy('c.name, c.firstname')
      ->limit($request->getParameter('limit'));
    if ( $request->getParameter('email') == 'true' )
    $q->andWhere("c.email IS NOT NULL AND email != ?",'');
    $q = Doctrine_Core::getTable('Contact')
      ->search($search.'*',$q);
    $request = $q->execute()->getData();

    $contacts = array();
    foreach ( $request as $contact )
      $contacts[$contact->id] = (string)($contact.' - '.$contact->email) ;
     
    $this->contacts = $contacts;
  }

  public function executeCsv(sfWebRequest $request, $labels = false)
  {
    require(dirname(__FILE__).'/csv.php');
  }

  // creates a group from filter criterias
  public function executeGroup(sfWebRequest $request)
  {
    require(dirname(__FILE__).'/group.php');
  }

  public function executeMap(sfWebRequest $request)
  {
    $q = $this->buildQuery();
    $this->gMap = new GMap();
    if ( !$this->gMap->getGMapClient()->getAPIKey() )
    {
      $this->getUser()->setFlash('error',__("The geolocalization module is not enabled, you can't access this function."));
      $this->redirect('index');
    }
    $this->gMap = Addressable::getGmapFromQuery($q,$request);
  }

  public function executeEmailing(sfWebRequest $request)
  {
    $this->redirect('email/new');
  }

  public function executeGetSpecializedForm(sfWebRequest $request)
  {
    $this->executeEdit($request);
    $this->form->displayOnly($this->field = $request->getParameter('field'));
    $this
      ->useClassicTemplateDir(true)
      ->setLayout('empty');
  }
  public function executeUpdate(sfWebRequest $request)
  {
    // BUG: 2013-04-12 & 2013-05-02
    $this->contact = $this->getRoute()->getObject();
    $this->form = $this->configuration->getForm($this->contact);
    if ( $request->getParameter('specialized-form',false) )
      $this->form->displayOnly();

    $this->processForm($request, $this->form);

    $this->setTemplate('edit');
  }

  public function executeVcf(sfWebRequest $request)
  {
    $this->executeShow($request);
    $this->useClassicTemplateDir(true);
  }

  public function executeCard(sfWebRequest $request)
  {
    return require(dirname(__FILE__).'/card.php');
  }

  protected function processForm(sfWebRequest $request, sfForm $form)
  {
    $form->bind($request->getParameter($form->getName()), $request->getFiles($form->getName()));
    if ($form->isValid())
    {
      $notice = $form->getObject()->isNew() ? 'The item was created successfully.' : 'The item was updated successfully.';

      $contact = $form->save();

      $this->dispatcher->notify(new sfEvent($this, 'admin.save_object', array('object' => $contact)));

      if ($request->hasParameter('_duplicate'))
      {
        $this->getUser()->setFlash('notice', $notice.' You can add another one below.');

        $contact = $contact->copy();
        $contact->slug = NULL;
        $contact->save();
        $this->redirect(array('sf_route' => 'contact_edit', 'sf_subject' => $contact));
      }
      elseif ($request->hasParameter('_save_and_add'))
      {
        $this->getUser()->setFlash('notice', $notice.' You can add another one below.');

        $this->redirect('@contact_new');
      }
      else
      {
        $this->getUser()->setFlash('notice', $notice);

        $this->redirect(array('sf_route' => 'contact_edit', 'sf_subject' => $contact));
      }
    }
    else
    {
      $this->getUser()->setFlash('error', 'The item has not been saved due to some errors.', false);
    }
  }

  public function executeFilter(sfWebRequest $request)
  {
    // remove cached data
    Doctrine::getTable('Cache')->createQuery('c')
      ->andWhere('c.domain = ?', 'rp-index')
      ->andWhere('c.identifier LIKE ?', '%/sideBar?sf_guard_user_id='.$this->getUser()->getId())
      ->delete()
      ->execute();

    if ( sfConfig::get('app_options_design',false) == 'tdp' && sfConfig::get(sfConfig::get('app_options_design').'_active',false) )
    {
      $this->setFilters($this->configuration->getFilterDefaults());
      $this->getUser()->setAttribute('organism.filters',$this->configuration->getFilterDefaults(),'admin_module');
    }
    return parent::executeFilter($request);
  }
  protected function getFilters()
  {
    if ( sfConfig::get('app_options_design') != 'tdp' || !sfConfig::get(sfConfig::get('app_options_design').'_active',false) )
      return parent::getFilters();

    $filters = parent::getFilters();
    $other_filters = $this->getUser()->getAttribute('organism.filters',null,'admin_module');
    if ( !(isset($filters['organism_category_id']) && is_array($filters['organism_category_id'])) )
      $filters['organism_category_id'] = array();
    if ( !(isset($filters['groups_list']) && is_array($filters['groups_list'])) )
      $filters['groups_list'] = array();
    if ( !(isset($other_filters['organism_category_id']) && is_array($other_filters['organism_category_id'])) )
      $other_filters['organism_category_id'] = array();
    if ( !(isset($other_filters['groups_list']) && is_array($other_filters['groups_list'])) )
      $other_filters['groups_list'] = array();
    $filters['organism_category_id'] = array_merge($filters['organism_category_id'], $other_filters['organism_category_id']);
    $filters['groups_list'] = array_merge($filters['groups_list'], $other_filters['groups_list']);
    $this->setFilters($filters);
    return $filters;
  }
  public static function sanitizeSearch($search)
  {
    $nb = mb_strlen($search);
    $charset = sfConfig::get('software_internals_charset');
    $transliterate = sfConfig::get('software_internals_transliterate',array());

    $search = str_replace(preg_split('//u', $transliterate['from'], -1), preg_split('//u', $transliterate['to'], -1), $search);
    $search = str_replace(MySearchAnalyzer::$cutchars,' ',$search);
    $search = preg_replace(array('!^[\d\w] !', '! [\d\w] !'), ' ', $search);
    $search = mb_strtolower(iconv($charset['db'],$charset['ascii'], mb_substr($search,$nb-1,$nb) == '*' ? mb_substr($search,0,$nb-1) : $search));
    return $search;
  }
}

<?php

require_once dirname(__FILE__).'/../lib/sfGuardUserGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/sfGuardUserGeneratorHelper.class.php';

/**
 * sfGuardUser actions.
 *
 * @package    sfGuardPlugin
 * @subpackage sfGuardUser
 * @author     Fabien Potencier
 * @version    SVN: $Id: actions.class.php 23319 2009-10-25 12:22:23Z Kris.Wallsmith $
 */
class sfGuardUserActions extends autoSfGuardUserActions
{
  public function executeIndex(sfWebRequest $request)
  {
    parent::executeIndex($request);
    if ( !$this->sort[0] )
    {
      $this->sort = array('username','');
      $this->pager->getQuery()->orderby('username');
    }
  }
  public function executeDelete(sfWebRequest $request)
  {
    $this->restrictDirectAccess($request);
    parent::executeDelete($request);
    $this->restrictVisualPermissions();
  }
  protected function executeBatchDelete(sfWebRequest $request)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers('I18N');
    $ids = $request->getParameter('ids');

    $q = Doctrine_Query::create()
      ->delete()
      ->from('sfGuardUser')
      ->whereIn('id', $ids);
    if ( !$this->getUser()->isSuperAdmin() )
      $q->andWhere('NOT is_super_admin');
    $count = $q->execute();

    if ($count >= count($ids))
      $this->getUser()->setFlash('notice', __('The selected items have been deleted successfully.'));
    else if ( $count == 0 )
      $this->getUser()->setFlash('error', __('A problem occured when deleting the selected items.'));
    else
    {
      $this->getUser()->setFlash('error', __('A problem occured when deleting some of the selected item (you probably tried to delete one or more (Super)Admin accounts without being a (Super)Admin yourself).'));
      $this->getUser()->setFlash('notice', __('Some of the selected items have been deleted successfully.'));
    }

    $this->redirect('@sf_guard_user');
  }
  public function executeNew(sfWebRequest $request)
  {
    $this->restrictGiveSuperAdminFlag($request);
    parent::executeNew($request);
    $this->restrictVisualPermissions();
  }
  public function executeCreate(sfWebRequest $request)
  {
    $this->restrictGiveSuperAdminFlag($request);
    parent::executeCreate($request);
    $this->restrictVisualPermissions();
  }
  public function executeUpdate(sfWebRequest $request)
  {
    $this->restrictDirectAccess($request);
    $this->restrictGiveSuperAdminFlag($request);
    Doctrine::getTable('Cache')->createQuery('c')
      ->andWhere('c.domain = ?', 'cache_files')
      ->andWhere('c.identifier ILIKE ?', 'global/menu%')
      ->delete()
      ->execute();
    parent::executeUpdate($request);
    $this->restrictVisualPermissions();
  }
  public function executeEdit(sfWebRequest $request)
  {
    $this->restrictDirectAccess($request,'sfGuardUser/show');
    parent::executeEdit($request);
    $this->restrictVisualPermissions();
  }
  public function executeDuplicate(sfWebRequest $request)
  {
    $user = $this->getRoute()->getObject()->copy();
    $user->username .= 'XX';
    $user->email_address = '_'.$user->email_address;
    
    // superstructure
    foreach ( $this->getRoute()->getObject()->Domain as $domain )
      $user->Domain[] = $domain->copy();
    
    // groups
    foreach ( $this->getRoute()->getObject()->Groups as $group )
      $user->Groups[] = $group;
    foreach ( $this->getRoute()->getObject()->AuthForGroups as $group )
      $user->AuthForGroups[] = $group;
    foreach ( $this->getRoute()->getObject()->AutoGroups as $group )
      $user->AutoGroups[] = $group;
    
    // ticketting elements
    foreach ( $this->getRoute()->getObject()->MetaEvents as $me )
      $user->MetaEvents[] = $me;
    foreach ( $this->getRoute()->getObject()->Workspaces as $ws )
      $user->Workspaces[] = $ws;
    foreach ( $this->getRoute()->getObject()->Prices as $price )
      $user->Prices[] = $price;
    
    $user->save();
    
    // mandatory RP fields
    $q = Doctrine::getTable('OptionMandatoryField')->createQuery('mf')->andWhere('mf.sf_guard_user_id = ?',$this->getRoute()->getObject()->id);
    foreach ( $q->execute() as $mf )
    {
      $mf = $mf->copy();
      $mf->sf_guard_user_id = $user->id;
      $mf->save();
    }
    
    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    $this->getUser()->setFlash('notice',__("The chosen user has been correctly duplicated, please verify its username and its email address"));
    $this->redirect('sfGuardUser/edit?id='.$user->id);
  }
  
  protected function restrictGiveSuperAdminFlag(sfWebRequest $request)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers('I18N');
    $sf_guard_user = isset($request['sf_guard_user']) ? $request['sf_guard_user'] : array();
    if ( isset($request['sf_guard_user']['is_super_admin']) && $request['sf_guard_user']['is_super_admin'] && !$this->getUser()->isSuperAdmin() )
    {
      $this->getUser()->setFlash('error',__("You can't add an account as a (Super)Admin if you're not a (Super)Admin youself. This flag has been simply deactivated."));
      unset($sf_guard_user['is_super_admin']);
    }
    $request->setParameter('sf_guard_user',$sf_guard_user);
    return $sf_guard_user;
  }
  protected function restrictViewSuperAdminFlag()
  {
    if ( !$this->getUser()->isSuperAdmin() )
      $this->form->setWidget('is_super_admin',new sfWidgetFormInputHidden());
  }
  protected function restrictDirectAccess(sfWebRequest $request, $redirect_route = '@sfGuardUser')
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers('I18N');
    if ( $request->getParameter('id') )
    {
      $sf_guard_user = Doctrine::getTable('SfGuardUser')->findOneById($request->getParameter('id'));
      if ( is_object($sf_guard_user) )
      if ( $sf_guard_user->is_super_admin && !$this->getUser()->isSuperAdmin()
        || in_array('admin-power',$sf_guard_user->getAllPermissionNames()) && (!$this->getUser()->hasCredential('admin-power')) )
      {
        $redirect_route = strpos('sfGuardUser/show',$redirect_route) !== false
          ? $redirect_route.'?id='.$request->getParameter('id')
          : $redirect_route;
        $this->getUser()->setFlash('error',__("You are not allowed to access account %%user%%, it is a (Super)Admin account.",array('%%user%%' => $sf_guard_user)));
        return $redirect_route ? $this->redirect($redirect_route) : $redirect_route;
      }
    }
  }
  protected function restrictVisualPermissions()
  {
    if ( !$this->getUser()->isSuperAdmin() )
    {
      $q = Doctrine::getTable('SfGuardPermission')->createQuery()
        ->whereIn('name',$this->getUser()->getCredentials())
        ->orderBy('name');
      $this->form->getWidget('permissions_list')->setOption('query',$q);
      
      $q = Doctrine::getTable('SfGuardGroup')->createQuery()
        ->whereIn('name',$this->getUser()->getGroupnames())
        ->orderBy('name');
      $this->form->getWidget('groups_list')->setOption('query',$q);
      
      $this->restrictViewSuperAdminFlag();
    }
  }
}

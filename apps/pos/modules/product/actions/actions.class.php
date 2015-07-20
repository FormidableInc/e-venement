<?php

require_once dirname(__FILE__).'/../lib/productGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/productGeneratorHelper.class.php';

/**
 * product actions.
 *
 * @package    e-venement
 * @subpackage product
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class productActions extends autoProductActions
{
  public function executeState(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    
    $q = Doctrine::getTable('Product')->createQuery('p')
      ->andWhere('p.id = ?', $request->getParameter('id'))
      ->orderBy('d.code');
    $this->forward404Unless($pdt = $q->fetchOne());
    
    $this->json = array(
      'id' => $pdt->id,
      'declinations' => array(),
      'texts' => array(
        'critical'  => __('Critical'),
        'correct'   => __('Correct'),
        'perfect'   => __('Good'),
      ),
    );
    
    foreach ( $pdt->Declinations as $declination )
      $this->json['declinations'][$declination->code] = array(
        'name' => $declination->name,
        'id'   => $declination->id,
        'current' => $declination->stock,
        'critical' => $declination->stock_critical,
        'perfect' => $declination->stock_perfect,
      );
    
    if ( sfConfig::get('sf_web_debug', false) && $request->hasParameter('debug') )
      return 'Success';
    return 'Json';
  }
  
  public function executeSalesEvolution(sfWebRequest $request)
  {
    $this->json = array();
    
    $q = Doctrine::getTable('Product')->createQuery('p')
      ->leftJoin('p.Declinations pd')
      ->leftJoin('pd.BoughtProducts bp')
      ->andWhere('bp.integrated_at IS NOT NULL')
      ->andWhere('bp.integrated_at > ?', date('Y-m-d', strtotime('1 year ago')))
      ->andWhere('p.id = ?', $request->getParameter('id'))
    ;
    
    for ( $i = 365 ; $i >= 0 ; $i-- )
      $this->json[date('Y-m-d', strtotime($i.' days ago'))] = 0;
    
    $this->forward404Unless($pdt = $q->fetchOne());
    foreach ( $pdt->Declinations as $declination )
    foreach ( $declination->BoughtProducts as $bp )
    {
      $date = date('Y-m-d', strtotime($bp->integrated_at));
      if ( !isset($this->json[$date]) )
        $this->json[$date] = 0;
      $this->json[$date]++;
    }
    
    if ( sfConfig::get('sf_web_debug', false) && $request->hasParameter('debug') )
      return 'Success';
    return 'Json';
  }
  public function executeDuplicate(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    $this->executeEdit($request);
    
    $copy = $this->product->copy();
    $copy->slug = NULL;
    foreach ( array('Translation', 'Declinations', 'PriceProducts',) as $cols )
    foreach ( $this->product->$cols as $col )
    {
      $ccol = $col->copy();
      
      if ( $col->getTable()->hasColumn('code') )
        $ccol->code = NULL;
      if ( $col->getTable()->hasColumn('name') )
        $ccol->name = $ccol->name.' ('.__('Copy').')';
      
      if ( $col->getTable()->hasRelation('Translation') )
      foreach ( $col->Translation as $i18n )
        $ccol->Translation[] = $i18n->copy();
      
      $copy->{$cols}[] = $ccol;
    }
    
    // links
    foreach ( array('LinkedManifestations', 'LinkedPrices', 'LinkedWorkspaces', 'LinkedMetaEvents') as $cols )
    foreach ( $this->product->$cols as $col )
      $copy->{$cols}[] = $col;
    
    $copy->save();
    $this->redirect('product/edit?id='.$copy->id);
  }
  public function executeAddDeclination(sfWebRequest $request)
  {
    $this->redirect('declination/new?product-id='.$request->getParameter('id'));
  }
  public function executeDelPicture(sfWebRequest $request)
  {
    Doctrine::getTable('Product')->find($request->getParameter('id', 0))->Picture->delete();
    return sfView::NONE;
  }
  public function executeDelDeclination(sfWebRequest $request)
  {
    Doctrine::getTable('ProductDeclination')->find($request->getParameter('declination_id', 0))->delete();
    return sfView::NONE;
  }
  
  public function executeAjax(sfWebRequest $request)
  {
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
    
    $q = Doctrine::getTable('Product')->createQuery('pdt')
      ->limit($request->getParameter('limit', $request->getParameter('max', 10)))
      ->leftJoin('pdt.MetaEvent me')
      ->andWhereIn('me.id IS NULL OR me.id', array_keys($this->getUser()->getMetaEventsCredentials()))
      ->andWhere('pt.lang = ?', $this->getUser()->getCulture())
      ->orderBy('pt.name')
    ;
    if ( ($tid = intval($request->getParameter('except_transaction', false))).'' === ''.$request->getParameter('except_transaction', false) )
      $q->andWhere('pdt.id NOT IN (SELECT bpd.product_id FROM BoughtProduct bp LEFT JOIN bp.Declination bpd WHERE bp.transaction_id = ? AND bp.product_declination_id IS NOT NULL)',$tid);
    
    // huge hack to look for declinations' codes AND product_index
    $q->andWhere('(TRUE')
      ->andWhere('d.code ILIKE ?', $request->getParameter('q').'%')
      ->orWhere('TRUE');
    $q = Doctrine_Core::getTable('Product')
      ->search($search.'*',$q);
    $q->andWhere('TRUE)');
    
    $this->products = array();
    foreach ( $q->execute() as $product )
    if ( $product->isAccessibleBy($this->getUser()) )
    if ( $request->hasParameter('keep-order') )
    {
      $this->products[] = array(
        'id'    => $product->id,
        'color' => NULL,
        'name'  => (string)$product,
      );
    }
    else
      $this->products[$product->id] = $request->hasParameter('with_colors')
        ? array('name' => (string)$product, 'color' => NULL)
        : (string) $product;
  }
  
  public function executeShow(sfWebRequest $request)
  {
    $this->getResponse()->addJavascript('pos-ro');
    $this->forward('product', 'edit');
  }
  public function executeEdit(sfWebRequest $request)
  {
    parent::executeEdit($request);
    
    if ( !$this->getUser()->hasCredential('pos-product-edit') )
      $this->getResponse()->addJavascript('pos-ro');
  }
}

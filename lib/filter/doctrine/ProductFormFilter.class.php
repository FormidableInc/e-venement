<?php

/**
 * Product filter form.
 *
 * @package    e-venement
 * @subpackage filter
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: sfDoctrineFormFilterTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class ProductFormFilter extends BaseProductFormFilter
{
  protected $user = NULL;
  /**
   * @see TraceableFormFilter
   */
  public function configure()
  {
    parent::configure();
    
    $this->widgetSchema   ['use_stock'] = new sfWidgetFormChoice(array(
      'choices' => $arr = array(
        ''  => 'yes or no',
        'y' => 'yes',
        'n' => 'no',
      ),
    ));
    $this->validatorSchema['use_stock'] = new sfValidatorChoice(array(
      'required' => false,
      'choices'  => array_keys($arr),
    ));
    $this->widgetSchema ['stock_status'] = new sfWidgetFormChoice(array(
      'choices' => $choices = array(
        ''        => '',
        'soldout' => 'Sold out',
        'critical'=> 'Critical',
        'correct' => 'Correct',
        'good'    => 'Good',
      ),
    ));
    $this->validatorSchema['stock_status'] = new sfValidatorChoice(array(
      'required' => false,
      'choices'  => array_keys($choices),
    ));
    
    $this->widgetSchema   ['prices_list']
      ->setOption('multiple', true)->setOption('order_by', array('name',''))
      ->setOption('query', $q = Doctrine::getTable('Price')->createQuery('p')->leftJoin('p.PricePOS pos')->andWhere('pos.id IS NOT NULL'))
    ;
    $this->validatorSchema['prices_list']->setOption('query', $q);
    
    $this->widgetSchema   ['meta_event_id']
      ->setOption('multiple', true)
      ->setOption('order_by', array('name',''))
      ->setOption('add_empty', false);
    $this->validatorSchema['meta_event_id']->setOption('multiple', true);
    
    $this->widgetSchema   ['product_category_id']->setOption('order_by', array('pct.name', ''));
    
    if ( !sfContext::hasInstance() )
      return;
    $this->user = sfContext::getInstance()->getUser();
    
    $this->widgetSchema   ['prices_list']->getOption('query')->leftJoin('p.Users u')->andWhere('u.id = ?', $this->user->getId());
    
    $this->widgetSchema   ['meta_event_id']->setOption('query', $q = Doctrine::getTable('MetaEvent')->createQuery('me')->andWhereIn('me.id', array_keys($this->user->getMetaEventsCredentials())));
    $this->validatorSchema['meta_event_id']->setOption('query', $q);
    
    $this->widgetSchema   ['code'] = new sfWidgetFormInput;
    $this->validatorSchema['code'] = new sfValidatorString(array(
      'required' => false,
    ));
  }
  
  public function getFields()
  {
    return parent::getFields() + array(
      'name' => 'Name',
      'code' => 'Code',
      'short_name' => 'ShortName'
    );
  }
  
  public function addUseStockColumnQuery($q, $field, $value)
  {
    if ( !$value )
      return $q;
    return $q->andWhere("d.$field = ?", $value == 'y' ? true : false);
  }
  public function addStockStatusColumnQuery($q, $field, $value)
  {
    if ( !$value )
      return $q;
    switch ( $value ) {
    case 'soldout':
      $q->andWhere('d.stock = ?',0);
      break;
    case 'critical':
      $q->andWhere('d.stock > ? AND d.stock <= d.stock_critical', 0);
      break;
    case 'correct':
      $q->andWhere('d.stock > d.stock_critical AND d.stock < d.stock_perfect');
      break;
    case 'good':
      $q->andWhere('d.stock >= d.stock_perfect');
      break;
    }
    return $q;
  }
  public function addNameColumnQuery($q, $field, $value) {
    if ( !$value['text'] )
      return $q;
    
    $q->andWhere('pt.name ILIKE ?', $value['text'].'%');
    if ( $this->user )
      $q->andWhere('pt.lang = ?', $this->user->getCulture());
    
    return $q;
  }
  public function addShortNameColumnQuery($q, $field, $value) {
    if ( !$value['text'] )
      return $q;
    
    $q->andWhere('pt.short_name ILIKE ?', $value['text'].'%');
    if ( $this->user )
      $q->andWhere('pt.lang = ?', $this->user->getCulture());
    
    return $q;
  }
  
  public function addCodeColumnQuery($q, $field, $value)
  {
    if ( !$value )
      return $q;
    
    $q->andWhere('d.code ILIKE ?', $value.'%');
    
    return $q;
  }
}

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
*    Copyright (c) 2006-2011 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php

/**
 * Control filter form.
 *
 * @package    e-venement
 * @subpackage filter
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: sfDoctrineFormFilterTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class ControlFormFilter extends BaseControlFormFilter
{
  protected $noTimestampableUnset = true;
  
  /**
   * @see TraceableFormFilter
   */
  public function configure()
  {
    parent::configure();
    
    $manifestations = Doctrine::getTable('Manifestation')->getTodayManifestations();
    $mid = $manifestations->execute()->getPrimaryKeys();
    $this->widgetSchema['checkpoint_id']->setOption('query', Doctrine::getTable('Checkpoint')->getFromManifestations($mid));

    $this->options['query'] = Doctrine::getTable('Control')->createQuery('c')
      ->leftJoin('c.Ticket tck')
      ->andWhereIn('tck.manifestation_id', $mid)
    ;
    
    $this->widgetSchema   ['manifestation_id'] = new sfWidgetFormDoctrineChoice(array(
      'model' => 'Manifestation',
      'add_empty' => true,
      'query' => $manifestations
    ));
    $this->validatorSchema['manifestation_id'] = new sfValidatorDoctrineChoice(array(
      'model' => 'Manifestation',
      'required' => false,
    ));
  }
  
  public function getFields()
  {
    return array_merge(
      array(
        'manifestation_id' => 'ManifestationId',
      ),
      parent::getFields());
  }
  
  public function addManifestationIdColumnQuery(Doctrine_Query $query, $field, $value)
  {
    $fieldName = $this->getFieldName($field);
    
    if ( $value )
    {
      $a = $query->getRootAlias();
      $query->addWhere('tck.manifestation_id = ?',$value)
      ;
    }
    
    return $query;
  }
}

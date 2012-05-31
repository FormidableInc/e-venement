<?php

/**
 * BaseEntry
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $event_id
 * @property Event $Event
 * @property Doctrine_Collection $ContactEntries
 * @property Doctrine_Collection $ManifestationEntries
 * 
 * @method integer             getEventId()              Returns the current record's "event_id" value
 * @method Event               getEvent()                Returns the current record's "Event" value
 * @method Doctrine_Collection getContactEntries()       Returns the current record's "ContactEntries" collection
 * @method Doctrine_Collection getManifestationEntries() Returns the current record's "ManifestationEntries" collection
 * @method Entry               setEventId()              Sets the current record's "event_id" value
 * @method Entry               setEvent()                Sets the current record's "Event" value
 * @method Entry               setContactEntries()       Sets the current record's "ContactEntries" collection
 * @method Entry               setManifestationEntries() Sets the current record's "ManifestationEntries" collection
 * 
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class BaseEntry extends sfDoctrineRecord
{
    public function setTableDefinition()
    {
        $this->setTableName('entry');
        $this->hasColumn('event_id', 'integer', null, array(
             'type' => 'integer',
             'notnull' => true,
             'unique' => true,
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasOne('Event', array(
             'local' => 'event_id',
             'foreign' => 'id',
             'onDelete' => 'CASCADE',
             'onUpdate' => 'CASCADE'));

        $this->hasMany('ContactEntry as ContactEntries', array(
             'local' => 'id',
             'foreign' => 'entry_id'));

        $this->hasMany('ManifestationEntry as ManifestationEntries', array(
             'local' => 'id',
             'foreign' => 'entry_id'));

        $timestampable0 = new Doctrine_Template_Timestampable();
        $this->actAs($timestampable0);
    }
}
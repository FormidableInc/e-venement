<?php

/**
 * BaseEntryElement
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $entry_id
 * @property integer $manifestation_entry_id
 * @property integer $contact_entry_id
 * @property boolean $second_choice
 * @property boolean $accepted
 * @property ContactEntry $ContactEntry
 * @property ManifestationEntry $ManifestationEntry
 * @property Doctrine_Collection $EntryTickets
 * 
 * @method integer             getEntryId()                Returns the current record's "entry_id" value
 * @method integer             getManifestationEntryId()   Returns the current record's "manifestation_entry_id" value
 * @method integer             getContactEntryId()         Returns the current record's "contact_entry_id" value
 * @method boolean             getSecondChoice()           Returns the current record's "second_choice" value
 * @method boolean             getAccepted()               Returns the current record's "accepted" value
 * @method ContactEntry        getContactEntry()           Returns the current record's "ContactEntry" value
 * @method ManifestationEntry  getManifestationEntry()     Returns the current record's "ManifestationEntry" value
 * @method Doctrine_Collection getEntryTickets()           Returns the current record's "EntryTickets" collection
 * @method EntryElement        setEntryId()                Sets the current record's "entry_id" value
 * @method EntryElement        setManifestationEntryId()   Sets the current record's "manifestation_entry_id" value
 * @method EntryElement        setContactEntryId()         Sets the current record's "contact_entry_id" value
 * @method EntryElement        setSecondChoice()           Sets the current record's "second_choice" value
 * @method EntryElement        setAccepted()               Sets the current record's "accepted" value
 * @method EntryElement        setContactEntry()           Sets the current record's "ContactEntry" value
 * @method EntryElement        setManifestationEntry()     Sets the current record's "ManifestationEntry" value
 * @method EntryElement        setEntryTickets()           Sets the current record's "EntryTickets" collection
 * 
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class BaseEntryElement extends sfDoctrineRecord
{
    public function setTableDefinition()
    {
        $this->setTableName('entry_element');
        $this->hasColumn('entry_id', 'integer', null, array(
             'type' => 'integer',
             'notnull' => true,
             ));
        $this->hasColumn('manifestation_entry_id', 'integer', null, array(
             'type' => 'integer',
             'notnull' => true,
             ));
        $this->hasColumn('contact_entry_id', 'integer', null, array(
             'type' => 'integer',
             'notnull' => true,
             ));
        $this->hasColumn('second_choice', 'boolean', null, array(
             'type' => 'boolean',
             ));
        $this->hasColumn('accepted', 'boolean', null, array(
             'type' => 'boolean',
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasOne('ContactEntry', array(
             'local' => 'contact_entry_id',
             'foreign' => 'id',
             'onDelete' => 'CASCADE',
             'onUpdate' => 'CASCADE'));

        $this->hasOne('ManifestationEntry', array(
             'local' => 'manifestation_entry_id',
             'foreign' => 'id',
             'onDelete' => 'CASCADE',
             'onUpdate' => 'CASCADE'));

        $this->hasMany('EntryTickets', array(
             'local' => 'id',
             'foreign' => 'entry_element_id'));

        $timestampable0 = new Doctrine_Template_Timestampable();
        $this->actAs($timestampable0);
    }
}
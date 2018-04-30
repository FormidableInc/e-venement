<?php

/**
 * Contact form.
 *
 * @package    e-venement
 * @subpackage form
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: sfDoctrineFormTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class ContactPublicForm extends ContactForm
{
  public function configure()
  {
    parent::configure();
    
    $this->disableLocalCSRFProtection();
    
    foreach ( array(
        'sf_guard_user_id', 'back_relations_list', 'Relationships', 'YOBs',
        'YOBs_list', 'groups_list', 'emails_list', 'family_contact', 'relations_list',
        'organism_category_id', 'description', 'password', 'email_no_newsletter', 'email_npai', 'npai', 'flash_on_control',
        'last_accessor_id', 'slug', 'confirmed', 'version', 'culture', 'picture_id',
        'shortname', 'involved_in_list', 'automatic',
        'familial_quotient_id', 'type_of_resources_id', 'familial_situation_id',
        'nationality', 'external_id',
      ) as $field )
      if ( isset($this->widgetSchema[$field]) )
      unset($this->widgetSchema[$field], $this->validatorSchema[$field]);
    
    $this->widgetSchema['title'] = new sfWidgetFormDoctrineChoice(array(
      'model' => 'TitleType',
      'add_empty' => true,
      'key_method' => 'getName',
    ));
    $this->widgetSchema['phone_type'] = new sfWidgetFormDoctrineChoice(array(
      'model' => 'PhoneType',
      'key_method' => '__toString',
      'add_empty' => true,
    ));
    
    $this->widgetSchema   ['password']        = new sfWidgetFormInputPassword();
    $this->widgetSchema   ['password_again']  = new sfWidgetFormInputPassword();
    $this->validatorSchema['password']        = new sfValidatorString(array('required' => true, 'min_length' => 4));
    $this->validatorSchema['password_again']  = new sfValidatorString(array('required' => false));
    $this->widgetSchema   ['password']->setLabel('New password');
    
    if ( sfConfig::get('app_contact_newsletter', true) )
    {
      $this->widgetSchema   ['newsletter']      = new sfWidgetFormInputCheckbox(array(
        'default' => !$this->object->isNew() && !$this->object->email_no_newsletter ? true : false,
        'value_attribute_value' => 'yes',
        'label' => 'I agree to receive e-mail newsletters',
      ));
      $this->validatorSchema['newsletter']      = new sfValidatorBoolean(array(
        'true_values' => array('yes'),
        'required' => false,
      ));
    }
    
    foreach ( array('firstname','address','postalcode','city','email') as $field )
      $this->validatorSchema[$field]->setOption('required', true);
    
    $fields = array(
      'id',
      'title','name','firstname',
      'country', 'postalcode','city','address',
      'email','phone_type','phone_number',
      'password','password_again',
    );
    if ( sfConfig::get('app_contact_newsletter', true) )
      $fields[] = 'newsletter';
      
    if ( sfConfig::get('app_contact_mailing', false) )
      $fields[] = 'no_mailing';
    else
      unset($this->widgetSchema['no_mailing'], $this->validatorSchema['no_mailing']);
      
    if ( sfContext::hasInstance() )
      $ext_auth = in_array('liOnlineExternalAuthOpenIDConnectPlugin', sfContext::getInstance()->getConfiguration()->getPlugins());
      
    if ( pubConfiguration::getText('app_texts_terms_conditions') && !$ext_auth )
    {
        $this->widgetSchema   ['terms_conditions']      = new sfWidgetFormInputCheckbox(array(
            'default' => false,
            'value_attribute_value' => 'yes',
            'label' => pubConfiguration::getText('app_texts_terms_conditions_url')?'<a href="' . url_for('cart/cgv') . '" target="_blank">'.__('Terms & Conditions').'</a>':pubConfiguration::getText('app_texts_terms_conditions'),
        ));
        $this->validatorSchema['terms_conditions']      = new sfValidatorBoolean(array(
            'true_values' => array('yes'),
            'required' => true,
        ));        
        $fields[] = 'terms_conditions';
    }    
    $this->widgetSchema->setPositions($fields);
    
    $this->validatorSchema['id'] = new sfValidatorDoctrineChoice(array(
      'model' => 'Contact',
      'query' => Doctrine_Query::create()->from('Contact c'),
      'required' => false,
    ));
    
    // if the contact is a professional
    if ( sfConfig::get('app_contact_professional', false) )
    {
      unset($this->widgetSchema['phone_type'], $this->validatorSchema['phone_type']);
      
      unset($this->widgetSchema['phone_number'], $this->validatorSchema['phone_number']);
      $this->widgetSchema   ['pro_phone_number'] = new sfWidgetFormInput;
      $this->validatorSchema['pro_phone_number'] = new sfValidatorString(array('required' => false));
      $this->widgetSchema   ['pro_phone_number']->setLabel('Phone number')->setDefault($this->object->Professionals[0]->contact_number);
      
      unset($this->widgetSchema['email'], $this->validatorSchema['email']);
      $this->widgetSchema   ['pro_email'] = new sfWidgetFormInput;
      $this->validatorSchema['pro_email'] = new sfValidatorEmail;
      $this->widgetSchema   ['pro_email']->setLabel('Email')->setDefault($this->object->Professionals[0]->contact_email);
      
      $this->widgetSchema['pro_organism'] = new sfWidgetFormInput(array(), array('disabled' => 'disabled'));
      $this->widgetSchema['pro_organism']->setDefault($this->object->Professionals[0]->Organism)->setLabel('Organism');
      $this->widgetSchema['pro_address'] = new sfWidgetFormTextarea(array(), array('disabled' => 'disabled'));
      $this->widgetSchema['pro_address']->setDefault(
        trim($this->object->Professionals[0]->Organism->address)
        ."\n".
        $this->object->Professionals[0]->Organism->postalcode
        ." ".
        $this->object->Professionals[0]->Organism->city
        ."\n".
        $this->object->Professionals[0]->Organism->country
      )->setLabel('Address');
      
      foreach ( array('address', 'postalcode', 'city', 'country') as $field )
        unset($this->widgetSchema[$field], $this->validatorSchema[$field]);
      
      $fields = array(
        'id',
        'title','name','firstname',
        'pro_organism', 'pro_address',
        'pro_email','pro_phone_number',
        'password','password_again',
      );
      if ( sfConfig::get('app_contact_newsletter', true) )
        $fields[] = 'newsletter';
      if ( sfConfig::get('app_contact_mailing', false) )
        $fields[] = 'no_mailing';
      if (pubConfiguration::getText('app_texts_terms_conditions'))
        $fields[] = 'terms_conditions';
      $this->widgetSchema->setPositions($fields);
    
      if ( sfConfig::get('app_contact_modify_coordinates_first', false) )
      {
        $this->widgetSchema   ['comment'] = new sfWidgetFormTextarea;
        $this->widgetSchema   ['comment']->setLabel('Some changes to submit?');
        $this->widgetSchema   ['comment']->setDefault(sfContext::getInstance()->getUser()->getTransaction()->Professional->description);
        $this->validatorSchema['comment'] = new sfValidatorString;
        $this->validatorSchema['comment']->setOption('required', false);
      }
    }
    
    $vel = sfConfig::get('app_tickets_vel',array());
    if ( isset($vel['one_shot']) && $vel['one_shot'] )
      unset($this->widgetSchema['password'], $this->widgetSchema['password_again']);
    
    if ( sfContext::hasInstance() )
      sfContext::getInstance()->getUser()->addCredential('pr-group-common');
    $q = Doctrine::getTable('Group')->createQuery('g')->andWhere('g.sf_guard_user_id IS NULL');
    if ( sfContext::hasInstance() )
      sfContext::getInstance()->getUser()->removeCredential('pr-group-common');
    if ( $q->count() > 0 )
    {
      $this->validatorSchema['special_groups_list'] = new sfValidatorDoctrineChoice(($arr = array(
        'model' => 'Group',
        'query' => $q,
        'multiple' => true,
      )) + array('required' => false));
      $this->widgetSchema   ['special_groups_list'] = new sfWidgetFormDoctrineChoice($arr + array(
        'expanded' => true,
        'order_by' => array('g.name', ''),
      ));
      $this->widgetSchema   ['special_groups_list']->setLabel('Options');
      $this->setDefault('special_groups_list', sfConfig::get('app_contact_professional', false)
        ? $this->object->Professionals[0]->Groups->getPrimaryKeys()
        : $this->object->Groups->getPrimaryKeys()
      );
    }
    
    // feature that allows adding fields as required or not through the app.yml
    if ( ($force = sfConfig::get('app_contact_force_fields', array())) && is_array($force) )
    foreach ( $force as $field => $required )
    if ( isset($this->validatorSchema[$field]) && !in_array($field, array('name', 'email')) )
      $this->validatorSchema[$field]->setOption('required', $required === true);
    
    foreach ($this->validatorSchema->getFields() as $field => $validator)
      if ( $this->validatorSchema[$field]->getOption('required') === true )
        $this->widgetSchema[$field]->setAttribute('class', 'required');
    
    // if the liOpenIDConnectPlugin is activated
    if ( in_array('liOnlineExternalAuthOpenIDConnectPlugin', sfContext::getInstance()->getConfiguration()->getPlugins()) )
    {
      $ws = $this->getWidgetSchema();
      $vs = $this->getValidatorSchema();
      unset(
        $ws['email'],
        $vs['email'],
        $ws['password'],
        $vs['password'],
        $ws['password_again'],
        $vs['password_again']
      );
    }
    
    if (isset($this->widgetSchema['password'])) {
      $this->validatorSchema->setPostValidator(
        new sfValidatorSchemaCompare(
          'password_again',
          sfValidatorSchemaCompare::EQUAL,
          'password',
          array(),
          array('invalid' => __('Passwords do not match. Please try again.'))
        )
      );
    }    
  }
  
  public function bind(array $taintedValues = NULL, array $taintedFiles = NULL)
  {
    parent::bind($taintedValues, $taintedFiles);
    
    // add a validator to avoid duplicates
    if ( $this->object->isNew() )
    {
      $q = Doctrine_Query::create()
        ->from('Contact c');
      $this->validatorSchema['duplicate'] = new liValidatorContact(array(
        'query' => $q,
        'required' => true,
      ));
      $q = $this->validatorSchema['duplicate']->getOption('query');
      foreach ( array('name', 'firstname', 'email') as $field )
        $q->andWhere("c.$field ILIKE ?",$this->getValue($field));
    }
    
    // bind again for the new validators
    parent::bind($taintedValues, $taintedFiles);
  }
  
  public function save($con = NULL)
  {
    // formatting central data
    foreach ( array('name', 'firstname') as $field )
      $this->values[$field] = trim($this->values[$field]);
    
    // formatting data
    if ( sfConfig::has('app_contact_capitalize') && is_array($fields = sfConfig::get('app_contact_capitalize')) )
    foreach ( $fields as $field )
    if ( isset($this->values[$field]) )
      $this->values[$field] = mb_strtoupper($this->values[$field], 'UTF-8');
    
    if ( is_null($this->object->confirmed) )
      $this->values['confirmed'] = false;
    
    if ( sfConfig::get('app_contact_newsletter', true) )
      $this->values['email_no_newsletter'] = !$this->values['newsletter'];
    
    if ( $this->getValue('phone_number') )
    {
      $new_number = true;
      foreach ( $this->object->Phonenumbers as $pn )
      if ( strcasecmp($pn->name,$this->getValue('phone_type')) == 0 )
      {
        $pn->number = $this->getValue('phone_number');
        $new_number = false;
        break;
      }
      
      if ( $new_number )
      {
        $pn = new ContactPhonenumber;
        $pn->name = $this->getValue('phone_type');
        $pn->number = $this->getValue('phone_number');
        
        $this->object->Phonenumbers[] = $pn;
      }
    }
    
    // if no password set
    if ( !$this->object->isNew() && !trim($this->getValue('password')) )
      $this->values['password'] = $this->object->password;
    
    // if the contact is a professional
    if ( sfConfig::get('app_contact_professional', false) )
    {
      foreach ( array('pro_email' => 'contact_email', 'pro_phone_number' => 'contact_number') as $vname => $field )
        $this->object->Professionals[0]->$field = $this->values[$vname];
      
      // the comment on coordinates
      if ( trim($this->getValue('comment')) && sfContext::hasInstance() )
      {
        $transaction = sfContext::getInstance()->getUser()->getTransaction();
        $transaction->Professional->description = $this->getValue('comment');
        $transaction->save();
      }
    }
    
    return parent::save($con);
  }
  
  public function saveGroupsList($con = NULL)
  {
    if (!$this->isValid())
      throw $this->getErrorSchema();
    
    // somebody has unset this widget
    if (!isset($this->widgetSchema['special_groups_list']))
      return;
    
    if (null === $con)
      $con = $this->getConnection();
    
    $object = sfConfig::get('app_contact_professional', false) ? $this->object->Professionals[0] : $this->object;
    
    if ( !$object->isNew() )
      return;
    
    $q = Doctrine_Query::create()->from('Group g')
      ->andWhere('g.sf_guard_user_id IS NULL')
      ->leftJoin('g.Users u')
      ->andWhere('u.id = ?', sfContext::getInstance()->getUser()->getId());
    $groups = $q->execute();
    
    sfContext::getInstance()->getUser()->addCredential('pr-group-common');
    $possible = $groups->getPrimaryKeys();
    $values = $this
      ->correctGroupsListWithCredentials('special_groups_list', $object)
      ->getValue('special_groups_list')
    ;
    sfContext::getInstance()->getUser()->removeCredential('pr-group-common');
    
    if (!is_array($values))
      $values = array();
    
    $unlink = array_diff($possible, $values);
    if (count($unlink))
      $object->unlink('Groups', array_values($unlink));
    
    foreach ( $values as $gid )
    if ( in_array($gid, $possible) )
    {
      foreach ( $groups as $group )
      if ( $group->id == $gid )
      {
        $object->Groups[] = $group;
      }
    }
    
    if ( sfConfig::get('app_contact_professional', false) )
    foreach ( sfContext::getInstance()->getUser()->getGuardUser()->AutoGroups as $group )
      $object->Groups[] = $group;
  }
  
  public function removePassword()
  {
    unset(
      $this->widgetSchema   ['password'],
      $this->widgetSchema   ['password_again'],
      $this->validatorSchema['password'],
      $this->validatorSchema['password_again']
    );
  }
}

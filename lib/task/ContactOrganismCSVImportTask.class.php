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
*    Copyright (c) 2006-2015 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2006-2015 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php
  /**
    * FORMAT: CSV
    * ENCODING: UTF-8
    * SEPARATOR: ,
    * CONTENT STRUCTURE:
    * Id,titre,Nom,Prénom,Adresse1,Adresse2,CP,Ville,Pays,Type Tel1,Tel1,Type Tel2,Tel2,email_perso,no_newsletter,Mémo,org_id,Organisme,Type_Organisme,admin_number,site_web,Address,CP,Ville,Pays,org_type_tel1,org_tel1,org_type_tel2,org_tel2,org_email,org_no_newsletter,Fonction_Type,Fonction_libellé,Service,pro_tel,pro_email,pro_no_newsletter
    *
    **/
?>
<?php
class ContactOrganismCSVImportTask extends sfBaseTask{

  protected function configure() {
    $this->addArguments(array(
      new sfCommandArgument('input', sfCommandArgument::REQUIRED, 'The file to parse'),
      new sfCommandArgument('go', sfCommandArgument::OPTIONAL, 'no'),
    ));
    $this->addOptions(array(
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environement', 'dev'),
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application', 'default'),
      new sfCommandOption('no-headers', sfCommandOption::PARAMETER_OPTIONAL),
    ));
    $this->namespace = 'e-venement';
    $this->name = 'rp-csv-import';
    $this->briefDescription = 'Imports CSV data into Contacts & Organisms';
    $this->detailedDescription = 'Imports CSV data into Contacts & Organisms using YTF8 + "," separator...';
  }

  protected function execute($arguments = array(), $options = array())
  {
    sfContext::createInstance($this->configuration, $options['env']);
    $databaseManager = new sfDatabaseManager($this->configuration);

    if (!( $fp = fopen($arguments['input'],'r') ))
      throw new liEvenementException('Error reading the file!!');

    // init
    $contacts  = new Doctrine_Collection('Contact');
    $organisms  = new Doctrine_Collection('Organism');
    $orgcats    = new Doctrine_Collection('OrganismCategory');
    foreach ( Doctrine::getTable('OrganismCategory')->createQuery('oc')->execute() as $oc )
      $orgcats[$oc->id] = $oc;
    $protypes   = new Doctrine_Collection('ProfessionalType');
    foreach ( Doctrine::getTable('ProfessionalType')->createQuery('pt')->execute() as $pt )
      $protypes[$pt->id] = $pt;

    if ( !$options['no-headers'] )
      fgetcsv($fp);
    for ( $l = 0 ; ($line = fgetcsv($fp)) ; $l++ )
    try
    {
      $organism = $contact = NULL;
      $i = 0;

      // there is a contact
      // if an id is given for the organism
      $contactrank = $i+1;
      if ( !trim($line[$i]) )
        $contact = new Contact;
      elseif ( isset($contacts[trim($line[$i])]) )
        $contact = $contacts[trim($line[$i])];
      else
        $contact = new Contact;
      if ( trim($line[$i++]) )
        $contact->id = trim($line[$i-1]);
      foreach ( array('title', 'name', 'firstname', 'address', 'address', 'postalcode', 'city', 'country') as $field )
        $contact->$field = trim($line[$i++]);
      $contact->address = trim($line[4].($line[5] ? "\n".$line[5] : ''));

      for ( $j = 0 ; $j < 2 ; $j++ )
      {
        $pn = new ContactPhonenumber;
        $pn->name = $line[$i++];
        $pn->number = $line[$i++];
        if ( trim($pn->number) )
          $contact->Phonenumbers[] = $pn;
      }

      $contact->email = trim($line[$i++]);
      $contact->email_no_newsletter = trim($line[$i++]) ? true : false;
      $contact->description = trim($line[$i++]);

      if ( trim($line[$contactrank]) )
      {
        if ( $arguments['go'] )
        {
          $contact->save();
          $contacts[$contact->id] = $contact;
        }
        $this->logSection('Contact', $contact.' added or updated with id #'.$contact->id);
      }

      // there is an organism
      // if an id is given for the organism
      $orgrank = $i+1;
      if ( !trim($line[$i]) || !isset($organisms[trim($line[$i])]) )
        $organism = new Organism;
      else
        $organism = $organisms[$line[$i]];

      if ( trim($line[$i++]) )
        $organism->id = trim($line[$i-1]);
      $organism->name = trim($line[$i++]);

      // Organism Category
      $cat = $line[$i++];
      if ( trim($cat) )
      {
        if ( ($key = array_search($cat, $orgcats->toKeyValueArray('id', 'name'))) !== false )
          $organism->Category = $orgcats[$key];
        else
        {
          $organism->Category->name = trim($cat);
          $organism->Category->save();
          $orgcats[$organism->Category->id] = $organism->Category;
        }
      }

      foreach ( array('administrative_number', 'url', 'address', 'postalcode', 'city', 'country') as $field )
        $organism->$field = trim($line[$i++]);

      for ( $j = 0 ; $j < 2 ; $j++ )
      {
        $pn = new OrganismPhonenumber;
        $pn->name = $line[$i++];
        $pn->number = $line[$i++];
        if ( trim($pn->number) )
          $organism->Phonenumbers[] = $pn;
      }

      $organism->email = $email = trim($line[$i++]);
      $organism->email_no_newsletter = trim($line[$i++]) ? true : false;

      if ( trim($line[$orgrank]) )
      {
        if ( $arguments['go'] )
        {
          $organism->save();
          $organisms[$organism->id] = $organism;
        }
        $this->logSection('Organism', $organism.' added or updated with id #'.$organism->id.' and '.$organism->email);
      }

      // there is a professional
      if ( trim($line[2]) && trim($line[$orgrank]) )
      {
        $pro = new Professional;
        $pro->Contact = $contact;
        $pro->Organism = $organism;

        $type = $line[$i++];
        if ( ($key = array_search($type, $protypes->toKeyValueArray('id', 'name'))) !== false )
          $pro->ProfessionalType = $protypes[$key];
        else
        {
          $pro->ProfessionalType = new ProfessionalType;
          $pro->ProfessionalType->name = $type;
          $pro->ProfessionalType->save();
          $protypes[$pro->ProfessionalType->id] = $pro->ProfessionalType;
        }

        foreach ( array('name', 'department', 'contact_number', 'contact_email') as $field )
          $pro->$field = $line[$i++];

        $pro->contact_email_no_newsletter = trim($line[$i++]) ? true : false;

        if ( $arguments['go'] )
          $pro->save();
        $this->logSection('Professional', 'A position has been added for contact '.$contact.' in organism '.$organism);
      }
    }
    catch ( Doctrine_Connection_Pgsql_Exception $e )
    {
      $this->logSection('ERROR', 'Line '.($l+1).': '.$line[0].' '.$e->getMessage(), 'ERROR');
    }

    fclose($fp);
    $this->logSection('Done', '... done '.(!$arguments['go'] ? 'but for fake: add the [go] parameter to do it for real' : ''));
    if ( $arguments['go'] )
      $this->logSection('Warning', 'Check out your sequences (contact_id_seq & organism_id_seq), if ids were given by your input file instead of the internal sequences.');
  }
}

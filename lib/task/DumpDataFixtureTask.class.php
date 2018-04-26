<?php

class DumpDataFixtureTask extends sfBaseTask
{
  
    protected function configure() 
    {
      $this->addArguments(array(
        new sfCommandArgument('table', sfCommandArgument::REQUIRED, 'The table to dump')
      ));
      $this->addOptions(array(
        new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      ));
      
      $this->namespace = 'e-venement';
      $this->name = 'dump-table';
      $this->briefDescription = 'Dump table to fixtures';
      $this->detailedDescription = <<<EOF
        The [aptaw:dump-table|INFO] Dump specified table name to yml fixture file:
        [./symfony e-venement:dump-table table_name [table_name] --env=dev|INFO]
EOF;
    }

    protected function execute($arguments = array(), $options = array()) 
    {
      if ( !array_key_exists('table', $arguments) && count($arguments['table']) == 0 ) 
      {
        $this->logSection('Dump', 'Nothing to dump');
      }
      else
      {
        $this->logSection('Dump', 'Start dumping...');

        $databaseManager = new sfDatabaseManager($this->configuration);
        $data = new Doctrine_Data();
        $parser = new Doctrine_Parser_Yml();

        $table_name = $arguments['table'];

        $this->logSection('Dump', 'Dumping table '.$table_name);
        
        $table = Doctrine::getTable($table_name);
        $q = Doctrine_Query::create()
          ->from("$table_name tn")
          ->select('tn.*');
                
        if ( $table->hasRelation('Translation') ) 
        {
          $q->leftJoin('tn.Translation ct')
            ->addSelect('ct.*');
        } 
        
        $results = $q->fetchArray();
        
        $parser->dumpData(array($table_name => $results), sfConfig::get('sf_root_dir').'/data/fixtures/'.$table_name.'.yml');

        $this->logSection('Dump', 'Tables dumped');  
      }
    }
}

?>
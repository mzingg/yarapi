<?php
class DoctrineModule extends Module {
	
	public function __construct(Module & $oModule) {
		if (is_null($oModule))
		  throw new Exception('Module passed to the DoctrineModule cannot be null');
		  
		if (!function_exists('doctrine_manager'))
		  throw new Exception(
		    'DoctrineModule depends on the function doctrine_manager() in the doctrine Module.
		    So DoctrineModule classes cannot be instantiated before to module loading process has completed'
		  );
		  
	  // Ensure that all configured connections are loaded into the manager as well
	  // by calling our doctrine_manager() function.
	  $oManager = doctrine_manager();

	  // Call the parent constructor to initialize module correctly
		parent::__construct($oModule->getName(), $oModule->getPath());
	}
	
	function loadModels() {
	  // We have no schema.yml so we can quit early 
	  if (!$this->isDoctrineEnabled()) return;
	  
	  // When the model is not genarated (yet) we can also quit here.
	  if (!$this->schemaExists()) return;
	
	  // Load models - we need conservative strategy because otherwise we get problems
	  // during bootstrap.
	  Doctrine::loadModels($this->getModelsPath(), Doctrine::MODEL_LOADING_CONSERVATIVE);  
	}

	public static function dropDatabases() {
	  Doctrine::dropDatabases();
	  return true;
	}
	
	public static function createDatabases() {
	  Doctrine::createDatabases();
	  return true;  
	}
	
	function generateModelsFromYaml() {
		if (!$this->isDoctrineEnabled())
		  throw new Exception('This module [%s] is not enabled for doctrine model generation (schema.yml file is missing).');
		  
		$aOptions = array(
		  'generateTableClasses' => true
		);
	  Doctrine::generateModelsFromYaml($this->getSchemaPath(), $this->getModelsPath(), $aOptions);
	  return true;  
	}
	
	function createTablesFromModels() {
		if (!$this->schemaExists())
		  throw new Exception('Generated model classes do not exist. Call \'generateModelsFromYaml\' first.');
		  
	  Doctrine::createTablesFromModels($this->getModelsPath());
	  return true;
	}
	
	function readFixtures() {
    if (!$this->isDoctrineEnabled())
      throw new Exception('This module [%s] is not enabled for doctrine model generation (schema.yml file is missing).');
      
		Doctrine_Core::loadData($this->getFixturesPath());		
	}
		
	public function getSchemaPath() {
		return $this->getPath() . '/schema.yml';
	}
	
  public function getModelsPath() {
    return $this->getPath() . '/models';
  }
  
  public function getFixturesPath() {
    return $this->getPath() . '/fixtures';
  }
  
  public function getGeneratedModelsPath() {
    return $this->getPath() . '/models/generated';
  }
  
  public function schemaExists() {
		return is_dir($this->getModelsPath()) && is_dir($this->getGeneratedModelsPath());
	}
	
	public function isDoctrineEnabled() {
		return is_file($this->getSchemaPath()) && is_readable($this->getSchemaPath());  
	}

	
	
}
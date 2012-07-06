<?php

// Include the Doctrine ORM framework - we can be sure that this module will only be included
// once so we need only require instead of require_once.
require('doctrine_1.2.1/Doctrine.php');

// Register the Doctrine autoloaders. The modelsAutoload we need because we use
// the Doctrine::MODEL_LOADING_CONSERVATIVE strategy (lazy loading).
spl_autoload_register(array('Doctrine', 'autoload'));
spl_autoload_register(array('Doctrine', 'modelsAutoload'));

function doctrine_manager() {
	static $oManager = null;
	
	if (!$oManager) {
		$oManager = Doctrine_Manager::getInstance();
		$oManager->setAttribute(Doctrine::ATTR_AUTOLOAD_TABLE_CLASSES, true);
		
		$aConnectionConfig = yarapi_load_config('doctrine_connections');
		foreach ($aConnectionConfig as $sConnectionName => $sDsn) {
			Doctrine_Manager::connection($sDsn, $sConnectionName);
		}
	}
	
	return $oManager;
}

function doctrine_after_load($oModule) {
	if (!$oModule) return;
	
	// Load mechanism does nothing, when no models are present.
  // Use the command infrastructure to generate models and tables from the schema.yml
	$oDoctrineModule = new DoctrineModule($oModule);
	$oDoctrineModule->loadModels();
}

function doctrine_command($sCommand) {
  if (!in_array($sCommand, array('dropDatabases', 'createDatabases', 'generateModelsFromYaml', 'createTablesFromModels', 'loadFixtures'))) return null;
  
  
  $oResult = null;
  switch ($sCommand) {
    case 'dropDatabases':
      DoctrineModule::dropDatabases();
      $oResult = 'dropDatabases successfully called';
      break;
    case 'createDatabases':
      DoctrineModule::createDatabases();
      $oResult = 'createDatabases successfully called';
      break;
    case 'generateModelsFromYaml':
      $oResult = _doctrine_call_generateModelsFromYaml();
      break;
    case 'createTablesFromModels':
      $oResult =_doctrine_call_createTablesFromModels();
      break;
    case 'loadFixtures':
      $oResult =_doctrine_call_readFixtures();
      break;
  }
  
  return $oResult;
}

function _doctrine_call_generateModelsFromYaml() {
	$aModules = Modules::findModulesByStatus('enabled');
	$aSuccessfullModules = array();
	foreach ($aModules as $sModuleName => $oModule) {
	  $oDoctrineModule = new DoctrineModule($oModule);
	  
	  if (!$oDoctrineModule->isDoctrineEnabled())
	    continue;

	  $aSuccessfullModules[] = $sModuleName;
	  $oDoctrineModule->generateModelsFromYaml();
	}
	
	if (count($aSuccessfullModules) == 0)
	  return 'No models generated';
	  
	return sprintf('Models for the following modules generated: %s.', implode(',', $aSuccessfullModules));
}

function _doctrine_call_createTablesFromModels() {
  $aModules = Modules::findModulesByStatus('enabled');
  $aSuccessfullModules = array();
  foreach ($aModules as $sModuleName => $oModule) {
    $oDoctrineModule = new DoctrineModule($oModule);
    
    if (!$oDoctrineModule->isDoctrineEnabled())
      continue;

    $aSuccessfullModules[] = $sModuleName;
    $oDoctrineModule->createTablesFromModels();
  }
  
  if (count($aSuccessfullModules) == 0)
    return 'No models generated';
    
  return sprintf('Models for the following modules generated: %s.', implode(',', $aSuccessfullModules));
}

function _doctrine_call_readFixtures() {
  $aModules = Modules::findModulesByStatus('enabled');
  $aSuccessfullModules = array();
  foreach ($aModules as $sModuleName => $oModule) {
    $oDoctrineModule = new DoctrineModule($oModule);
    
    if (!$oDoctrineModule->isDoctrineEnabled())
      continue;

    $aSuccessfullModules[] = $sModuleName;    
    $oDoctrineModule->readFixtures(); 
  }
  
  if (count($aSuccessfullModules) == 0)
    return 'No fixtures read';
    
  return sprintf('Fixtures for the following modules read: %s.', implode(',', $aSuccessfullModules));
}
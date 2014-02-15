<?php

// Include the Doctrine ORM framework - we can be sure that this module will only be included
// once so we need only require instead of require_once.
require('DoctrineORM-2.2.2/Doctrine/ORM/Tools/Setup.php');

// Register the Doctrine autoloaders.
Doctrine\ORM\Tools\Setup::registerAutoloadDirectory('DoctrineORM-2.2.2');

/*
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
*/

function doctrine_after_load($oModule) {
	if (!$oModule) return;
	
	// Load mechanism does nothing, when no models are present.
  // Use the command infrastructure to generate models and tables from the schema.yml
	$oDoctrineModule = new DoctrineModule($oModule);
	//$oDoctrineModule->loadModels();
}

function doctrine_command($sCommand) {
  if (!in_array($sCommand, array('createTablesFromModels', 'loadFixtures'))) return null;
  
  $oResult = null;
  switch ($sCommand) {
    case 'createTablesFromModels':
      $oResult =_doctrine_call_createTablesFromModels();
      break;
    case 'loadFixtures':
      $oResult =_doctrine_call_readFixtures();
      break;
  }
  
  return $oResult;
}

function _doctrine_call_createTablesFromModels() {
  $aModules = Modules::findModulesByStatus('enabled');
  $aSuccessfullModules = array();
  foreach ($aModules as $sModuleName => $oModule) {
    $oDoctrineModule = new DoctrineModule($oModule);
    
    if (!$oDoctrineModule->isDoctrineEnabled())
      continue;

    if ($oDoctrineModule->createTablesFromModels()) {
    	$aSuccessfullModules[] = $sModuleName;
    }    
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
<?php

function unittest_request(Task $oTask, $oResult) {
	// Only answer to /yarapi/testrunner
  if ($oTask->getPathCount() != 2 || $oTask->getPathArg(0) != 'yarapi' || $oTask->getPathArg(1) != 'testrunner') return;
	
  $oTestSuite = _unittest_get_or_instantiate_suite();
  $oTestResult = new PHPUnit_Framework_TestResult();
  
  $oTestSuite->run($oTestResult);
  
  yarapi_debug($oTestResult);
  
	$oTask->setHandled();
}

function unittest_after_load(Module $oModule) {
  if (!$oModule) return;
	
	static $bLibraryLoaded = false;	
	if (!$bLibraryLoaded) {
		$oUnitTestModule = __unittest_module();
		$sPhpUnitLibraryPath = $oUnitTestModule->getPath() . '/lib/PHPUnit-3.4.9';
		set_include_path($sPhpUnitLibraryPath . PATH_SEPARATOR . get_include_path());
		require_once('PHPUnit/Framework.php');
		$bLibraryLoaded = true;
	}
	
	$oTestSuite = _unittest_get_or_instantiate_suite();
	
	// Search module for test classes.
	// Test classes are identified by being inside a tests directory and
	// having the extension .class.php. It is assumed that the class has the
	// same name as the file.
  $aTestClassNames = _unittest_scan_module_directory($oModule); 
  foreach ($aTestClassNames as $sTestClass) {
  	$oTestSuite->addTestSuite($sTestClass);
  } 
}

function _unittest_get_or_instantiate_suite() {
  static $oYarapiTestSuite = null;
  
  if (is_null($oYarapiTestSuite)) {
    $oYarapiTestSuite = new PHPUnit_Framework_TestSuite();
  }
	
  return $oYarapiTestSuite;
}

function _unittest_scan_module_directory(Module $oModule) {
	// We use this configuration to exclude unwanted directories
  $aModulesConfiguration = yarapi_load_config('modules');
    
  $sTestDir = $oModule->getPath() . '/tests';
  
  if (!is_dir($sTestDir) || !is_readable($sTestDir))
    return array();
    
  $aResult = array();
  
  $aFiles = scandir($sTestDir);
  foreach ($aFiles as $sSubEntry) {
    if ($sSubEntry == '.' || $sSubEntry == '..')
      continue;
      
    // if the directory is one of the excluded entries do nothing
    if (in_array($sSubEntry, $aModulesConfiguration['ignored_directories']))
      continue;
      
    $nExtensionIndex = strpos($sSubEntry, '.class.php'); 
    if ($nExtensionIndex === false)
      continue;
      
    $aResult[] = substr($sSubEntry, 0, $nExtensionIndex);
    // Require the class file so that the class is known
    // TODO: Better add to Installation state include and let the classload magic handle it?
    require_once($sTestDir . '/' . $sSubEntry);
  }
  
  return $aResult;
}
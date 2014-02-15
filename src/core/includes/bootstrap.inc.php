<?php
/**
 * Environment setup for the YARAPI core. Defines important global
 * variables, include paths and class autoloading.
 *
 * @author Markus Zingg
 * @version $Id$
 * @copyright yarapi.org, 23 August, 2009
 * @package core
 **/

// YAML Library: Hard coded lib requirement - needed in InstallationState
// The path is relative to the index.php because thats the executed script.
require ('core/lib/spyc-0.5.1/spyc.php');

// Hard coded class requirement - because the class is needed itself in the __autoload function
// The path is relative to the index.php because thats the executed script.
require_once ('core/classes/PersistentState.class.php');
require_once ('core/classes/InstallationState.class.php');

// Absolute Root path of the yarapi installation
$sRootPath = realpath(dirname(__FILE__) . '/../..');

// Environment definition - change through the Apache environment
// variable YARAPI_ENV (dev, stage, prod). Standard is 'dev'.
$sEnvironment = (array_key_exists('YARAPI_ENV', $_SERVER) ? $_SERVER['YARAPI_ENV'] : 'dev');

// Create the state object for this installation
$oInstallationState = InstallationState::create($sRootPath, $sEnvironment);

// TODO: move that in own class (Sites?)
$oInstallationState->registerIncludePath('/sites/' . $_SERVER['HTTP_HOST'] . '/lib');

// Register important lookup and shutdown functions
register_shutdown_function('__yarapi_shutdown');
spl_autoload_register('__yarapi_autoload');

// Include required core functions
require ('logging.inc.php');
require ('configuration.inc.php');
require ('authentication.inc.php');
require ('request.inc.php');

/**
 * Shutdown hook mainly for persisting YARAPI state.
 *
 * @return void
 */
function __yarapi_shutdown() {
   // Persist YARAPI state to the /var directory when the process ends
   $oInstallationState = InstallationState::getInstance();
   $oInstallationState->persist();
}

/**
 * The 'magical' class finder to look for classes
 * in the classpath directories defined in the YARAPI state.
 */
function __yarapi_autoload($sClassName) {
   // First, if the class exists already or $sClassName is invalid, do nothing.
   // Note the second paramtere of class_exists indicating it should NOT use __autoload
   if (! $sClassName || ! is_string($sClassName) || class_exists($sClassName, false)) {
      return;
   }
   
   // Append .class.php to find include file
   $sClassInclude = $sClassName . '.class.php';
   
   // Get the installation state instance
   $oInstallationState = InstallationState::getInstance();
   
   // List of all root directories for classes relative to index.php
   $aClassRootDirectories = array($oInstallationState->getInstallationRoot() . '/core/classes');
   
   // When the modules functionality was loaded, include also module paths
   if (class_exists('Modules', false) && class_exists('Module', false)) {
      $aEnabledModules = Modules::findModulesByStatus('enabled');
      foreach ($aEnabledModules as $oModule) {
         $aClassRootDirectories[] = $oModule->getPath() . '/classes';
      }
   }
   
   // Look in the provided class roots for the class include file
   // Core always takes precedence.
   foreach ($aClassRootDirectories as $sClassRoot) {
      $sAbsoluteIncludePath = $sClassRoot . '/' . $sClassInclude;
      
      // When the file exists include it and break the loop
      if (file_exists($sAbsoluteIncludePath)) {
         require_once ($sAbsoluteIncludePath);
         break;
      }
   }
}
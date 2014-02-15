<?php

// Include the PEAR Log package
require ('Log-1.12.7/Log.php');

// Initialize the YARAPI_LOGGER variable
__yarapi_init_logger();

// Replace the standard error_handler with our own
set_error_handler('__yarapi_error_handler');

function __yarapi_init_logger() {
   if (! class_exists('Log')) {
      error_log('Could not initialize logging system');
      return;
   }
   
   $oPearLogger = Log::singleton('error_log', PEAR_LOG_TYPE_SYSTEM, 'yarapi');
   
   $afileLoggerConfiguration = array('mode' => 0600, 'timeFormat' => '%d.%m.%Y %H:%M:%S');
   $oInstallationState = InstallationState::getInstance();
   $sFilename = $oInstallationState->getLogDirectoryPath() . '/yarapi.log';
   $oYarapiLogger = Log::singleton('file', $sFilename, null, $afileLoggerConfiguration);
   
   global $YARAPI_LOGGER, $sEnvironment;
   $YARAPI_LOGGER = Log::singleton('composite');
   $YARAPI_LOGGER->addChild($oPearLogger);
   $YARAPI_LOGGER->addChild($oYarapiLogger);
   
   // We log also to firebug, if we are in development environment
   /*
    * if ($sEnvironment == 'dev') { $oFirebugLogger = Log::singleton('firebug', '', 'yarapi', array('buffering' => true)); $YARAPI_LOGGER->addChild($oFirebugLogger); }
    */
   
   // Configure PHP error reporting: All errors in dev evironment, only errors otherwise
   if ($sEnvironment == 'dev') {
      error_reporting(E_ALL);
   } else {
      error_reporting(E_ERROR);
   }
}

/**
 * A replacement for the standard PHP error_handler using the PEAR Log framework
 * logging to error_log().
 */
function __yarapi_error_handler($code, $sMessage, $sFile, $nLine) {
   if (! class_exists('Log')) {
      error_log('Could not initialize logging system');
      return;
   }
   
   global $YARAPI_LOGGER;
   
   /* Map the PHP error to a Log priority. */
   switch ($code) {
      case E_WARNING :
      case E_USER_WARNING :
         $priority = PEAR_LOG_WARNING;
         break;
      case E_NOTICE :
      case E_USER_NOTICE :
         $priority = PEAR_LOG_NOTICE;
         break;
      case E_ERROR :
      case E_USER_ERROR :
         $priority = PEAR_LOG_ERR;
         break;
      default :
         $priority = PEAR_LOG_INFO;
   }
   
   $YARAPI_LOGGER->log($sMessage . ' in ' . $sFile . ' at line ' . $nLine, $priority);
}

/**
 * Write a message to the error_log
 */
function yarapi_log($sMessage, $priority = PEAR_LOG_INFO) {
   if (! class_exists('Log')) {
      error_log('Could not initialize logging system');
      return;
   }
   
   global $YARAPI_LOGGER;
   $YARAPI_LOGGER->log($sMessage, $priority);
}

/**
 * Dumps a variable to the firebug console.
 */
function yarapi_debug($var) {
   if (! class_exists('Log')) {
      error_log('Could not initialize logging system');
      return;
   }
   
   global $YARAPI_LOGGER;
   $YARAPI_LOGGER->log(print_r($var, true), PEAR_LOG_DEBUG);
}
?>
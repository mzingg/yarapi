<?php

// Include the PEAR Log package 
require('Log-1.11.5/Log.php');

// Replace the standard error_handler with our own
set_error_handler('__yarapi_error_handler');

// Configure PHP error reporting: All errors in dev evironment, only errors otherwise
$sEnvironment = InstallationState::getInstance()->getEnvironment();
if ($sEnvironment == 'dev') {
	error_reporting(E_ALL);
} else {
	error_reporting(E_ERROR);
}

/**
 * A replacement for the standard PHP error_handler using the PEAR Log framework
 * logging to error_log().
 */
function __yarapi_error_handler($code, $sMessage, $sFile, $nLine) {
	if (!class_exists('Log')) error_log('Could not initialize logging system. Make sure you installed the PEAR:Log package.');

	$oErrorLogLogger =  &Log::singleton('error_log', PEAR_LOG_TYPE_SYSTEM, 'yarapi');
  $oLogger = &Log::singleton('composite');
	$oLogger->addChild($oErrorLogLogger);
	
	// We log also to firebug, if we are in development environment
	$sEnvironment = InstallationState::getInstance()->getEnvironment();
	if ($sEnvironment == 'dev') {
		$oFirebugLogger = &Log::singleton('firebug', '', 'yarapi');
		$oLogger->addChild($oFirebugLogger);
	}

  /* Map the PHP error to a Log priority. */
  switch ($code) {
    case E_WARNING:
    case E_USER_WARNING:
        $priority = PEAR_LOG_WARNING;
        break;
    case E_NOTICE:
    case E_USER_NOTICE:
        $priority = PEAR_LOG_NOTICE;
        break;
    case E_ERROR:
    case E_USER_ERROR:
        $priority = PEAR_LOG_ERR;
        break;
    default:
        $priority = PEAR_LOG_INFO;
  }

  $oLogger->log($sMessage . ' in ' . $sFile . ' at line ' . $nLine, $priority);
}

/**
 * Write a message to the error_log
 */
function yarapi_log($sMessage, $priority = PEAR_LOG_INFO) {
	if (!class_exists('Log')) error_log('Could not initialize logging system. Make sure you installed the PEAR:Log package.');

	$oErrorLogLogger =  &Log::singleton('error_log', PEAR_LOG_TYPE_SYSTEM, 'yarapi');
  $oLogger = &Log::singleton('composite');
	$oLogger->addChild($oErrorLogLogger);
	
	// We log also to firebug, if we are in development environment
	$sEnvironment = InstallationState::getInstance()->getEnvironment();
	if ($sEnvironment == 'dev') {
		$oFirebugLogger = &Log::singleton('firebug', '', 'yarapi');
		$oLogger->addChild($oFirebugLogger);
	}
	
	$oLogger->log($sMessage, $priority);
}

/**
 * Dumps a variable to the firebug console.
 */
function yarapi_debug($var) {
  if (!class_exists('Log')) error_log('Could not initialize logging system. Make sure you installed the PEAR:Log package.');

	// If we are in NOT development environment, log a warning that this function was called
	$sEnvironment = InstallationState::getInstance()->getEnvironment();
	if ($sEnvironment != 'dev') {
		yarapi_log('yarapi_debug() has been called. This is not recommended in a production environment.', PEAR_LOG_WARNING);
	}

	$oLogger = &Log::singleton('firebug', '', 'yarapi');
	
	$oLogger->log(print_r($var, true));
} 
?>
<?php

function rest_request(& $oTask, & $oResult) {
	
	// stores the relations artifact -> modules
	$aArtifactsToModule = array();
	
	// Build the necessary maps to handle this request based on the 'rest_info' hook
	// of the modules enabled for REST processing.
	$aRestModules = Modules::invokeHookModular('rest_info');
	foreach ($aRestModules as $sModuleName => $aRestInfo) {
		
		// If the module provides any artifacts, we store them in the map $aArtifactsToModule
		if (is_array($aRestInfo) && array_key_exists('artifacts', $aRestInfo) && is_array($aRestInfo['artifacts'])) {
			foreach ($aRestInfo['artifacts'] as $sArtifact) {
				// If the entry for this artifact does not exist yet: create it
				if (!array_key_exists($sArtifact, $aArtifactsToModule)) {
					$aArtifactsToModule[$sArtifact] = array();
				}
					
				// Now we can add the artifact -> module relation
				$aArtifactsToModule[$sArtifact][$sModuleName] = Modules::findModuleByName($sModuleName);
			}
		}
		
	}
	
	// The request type is the first part of the REST request, it can be 'artifact' or 'relation'
	$sRequestType = $oTask->getPathArg(0);
	
	// If the request type is invalid we do nothing
	if (!$sRequestType || !in_array($sRequestType, array('artifact', 'relation'))) return;
	
	switch ($sRequestType) {
		case 'artifact':
			
			// First we need to get the name of the requested artifact. If that is not provided
			// we log an error and do nothing.
			// TODO: Maybe allow that and return a list of defined artifacts??
			$sRequestedArtifact = $oTask->getPathArg(1);
			if (!$sRequestedArtifact) {
				yarapi_log('You need to specify the name of the artifact.', PEAR_LOG_ERR);
				return;
			}
			
			// First determine the allowed HTTP options for the requestd artifact. For that
			// we ask all modules through the 'rest_options' hook.
			$aOptionsCallResult = Modules::invokeHookModular($aArtifactsToModule[$sRequestedArtifact], 'rest_options', $oTask);

			// Now we find all modules of this artifact/task for which this task is allowed. The result of an allowed task
			// is the parameter object for the method hook.
			$aAllowedCallResult = Modules::invokeHookModular($aArtifactsToModule[$sRequestedArtifact], 'rest_allowed', $oTask);
			
			// Prepare to actually handling the request
			$sRequestMethod = $oTask->getMethod();
			$sRequestHookName = $sRequestMethod . '_' .$sRequestedArtifact;
			
			// Determine what modules are really allowed to be called by comparing the result of the rest_allowed call
			// with the supported options.
			foreach ($aArtifactsToModule[$sRequestedArtifact] as $sModuleName => $oModule) {
				
				// Test if this module allows this call - if not continue
				// Also, if the hook not exists, we allow all calls
				if (array_key_exists($sModuleName, $aAllowedCallResult) && $aAllowedCallResult[$sModuleName] === false) continue;
				
				// Get the supported Options for this module. Defaults to rest_default_options()
				$aOptions = rest_default_options();
				if (array_key_exists($sModuleName, $aOptionsCallResult) && count($aOptionsCallResult[$sModuleName]) > 0) {
					$aOptions = $aOptionsCallResult[$sModuleName];
				}
				
				// When the actual request method is not part of the hook result also do nothing
				if (!in_array($sRequestMethod, $aOptions)) return;
				
				// ok, now we can be sure that the call is allowed, so we call it with the parameter
				// from the rest_allowed hook
				$parameter = null;
				if (array_key_exists($sModuleName, $aAllowedCallResult)) {
					$parameter = $aAllowedCallResult[$sModuleName];
				}
				// TODO: Was war parameter schon wieder???
				$oModule->callHook($sRequestHookName, $oTask, $oResult);
				
				// Set the 'handled' flag indicating that a result has been generated
				$oTask->setHandled();
			}
			
			break;
		case 'relation':
		  break;
	}
	
}

function rest_default_options() {
	return array('head', 'get', 'post', 'put', 'delete', 'options');
}
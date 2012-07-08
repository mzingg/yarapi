<?php

function yarapi_load_config($sConfigName) {
  
	$oInstallationState = InstallationState::getInstance();
	$sRootDirectory = $oInstallationState->getInstallationRoot();
	$sEnvironment = $oInstallationState->getEnvironment();

	// Fill array with search paths (do not forget the / at the end)
	$aSearchPath = array(
		// Highest priority the env directory in the host specifict tree
		$sRootDirectory . '/sites/' . $_SERVER['HTTP_HOST'] . '/config/' . $sEnvironment . '/',
		// Now the config dir in the host specific tree
		$sRootDirectory . '/sites/' . $_SERVER['HTTP_HOST'] . '/config/', 
		// The env directory in the all tree
		$sRootDirectory . '/sites/all/config/' . $sEnvironment . '/',
		// At last the config dir in the all tree
		$sRootDirectory . '/sites/all/config/'
	);
	
	// Now check the search path according to priority wether the file exists
	foreach ($aSearchPath as $sPath) {
		$sConfigFileName = $sPath . $sConfigName . '.yml';
		if (file_exists($sConfigFileName) && is_readable($sConfigFileName)) {
			return spyc_load_file($sConfigFileName);
		}
	}
	
	//yarapi_log(sprintf('Configuration [%s] does not exist. Returning empty array.', $sConfigName));
	return array();
}
?>
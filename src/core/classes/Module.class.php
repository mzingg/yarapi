<?php

/**
 * undocumented class
 *
 * @package core
 */
class Module {
	
	// input fields
	private $sModuleName;
	private $sAbsoluteDirectoryPath;
	
	// derived fields from info file
	private $sStatus;
	private $sDescription;
	
	private $nCompatibleMajor;
	private $nCompatibleMinor;
	private $sCompatibleRevision;
	
	private $nVersionMajor;
	private $nVersionMinor;
	private $sVersionRevision;
	
	private $aDependencies;
	
	private static $INFO_NAME = 'info.yml';
	private static $MODULE_SUFFIX = '.module.php';
	
	public function __construct($sModuleName, $sAbsoluteDirectoryPath) {
	
		if (!is_dir($sAbsoluteDirectoryPath) || !is_readable($sAbsoluteDirectoryPath)) {
			yarapi_log(
				sprintf('Module directory (%s) for module %s does not exist or is not readable.', $sAbsoluteDirectoryPath, $sModuleName),
				PEAR_LOG_ERR
			);
		}
		
		$this->sModuleName = $sModuleName;
		$this->sAbsoluteDirectoryPath = $sAbsoluteDirectoryPath;
		$this->oParentModule = null;
		
		$this->applyInfoData();
	}
	
	/**
	 * Binds the module functions to the current request by requiring the module PHP file.
	 * Only works when the status of the module is 'enabled'.
	 * CAUTION: This does not check the dependencies to be included as well
	 *
	 * @return void
	 */
	public function load() {
		// nothing to do when the module is not enabled
		if ($this->getStatus() != 'enabled' || $this->isLoaded()) return;
		
		$sModulePhpFile = $this->getModulePhpFileName();
		if (!file_exists($sModulePhpFile) || !is_readable($sModulePhpFile)) {
			yarapi_log(
				sprintf('Module entry file (%s) for module %s does not exist or is not readable.', $sModulePhpFile, $this->getName()),
				PEAR_LOG_WARNING
			);
			return;	
		}
		
		// Add the module lib path to the include path
		set_include_path(get_include_path() . PATH_SEPARATOR . $this->sAbsoluteDirectoryPath . '/lib');
		
		// require module file and make all functions in it glabally known
		require($sModulePhpFile);
		
		// Create the indicator function showing that this module is loaded
		eval(
			sprintf(
				'function %s() { return Modules::findModuleByName("%s"); }',
				$this->getLoadIndicatorFunctionName(),
				$this->getName()
			)
		);
		
		// If all is well we call the after_load hook of all enabled modules
		Modules::invokeHook('after_load', $this);
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 */
	public function isLoaded() {
		return function_exists($this->getLoadIndicatorFunctionName());
	}
	
	/**
	 * undocumented function
	 *
	 * @param string $sHookName 
	 * @return void
	 */
	public function providesHook($sHookName) {
		if (!$sHookName) return false;
		
		$sFunctionName = $this->getHookFunctionName($sHookName);
		$bModuleIsLoaded = $this->isLoaded();
		$bFunctionExists = function_exists($sFunctionName);
		
		// Check first if the function was defined by another module
		if (!$bModuleIsLoaded && $bFunctionExists) {
			yarapi_log(
				sprintf('Hook %s (%s) was defined by a wrong module (NOT %s). Please check your modules for wrong hook definitions.',
					$sHookName, $sFunctionName, $this->getName()
				),
				PEAR_LOG_ERR
			);
			return false;
		}
		
		if (!$bModuleIsLoaded && !$bFunctionExists) {
			$this->load();
			$bModuleIsLoaded = $this->isLoaded();
			$bFunctionExists = function_exists($sFunctionName);
		}
		
		// If the Module was loaded already we can just check for the function to exist
		return ($bModuleIsLoaded && $bFunctionExists);
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 */
	public function callHook() {	
		$aArguments = func_get_args();
		// First argument is the name of the hook, rest parameters to the hook
		$sHookName = array_shift($aArguments);
		
		// Check if this module provides this hook (and lazy load at the same time)	
		if (!$this->providesHook($sHookName)) return null;
		
		// Prepare for calling the hook function (we are sure it exists here)
		$sFunctionName = $this->getHookFunctionName($sHookName);		
		
		// Call the hook aspect hooks (only if we are not calling one of them)
		$sHookAspectNames = array('hook_before', 'hook_after');
		if (!in_array($sHookName, $sHookAspectNames)) {
			// Now invoke the hook_before hook to do things before the original hook
			Modules::invokeHook('hook_before', $sHookName, $this->getName(), $aArguments);
		}
		
		// Call the actual hook function with the provided parameters
		$result =  call_user_func_array($sFunctionName, $aArguments);
		
		// Call the hook aspect hooks (only if we are not calling one of them)
		if (!in_array($sHookName, $sHookAspectNames)) {
			// Allow other modules to do things after the original hook
			Modules::invokeHook('hook_after', $sHookName, $this->getName(), $aArguments, $result);
		}
		
		return $result;
	}
	
	public function getName() { return $this->sModuleName; }
	public function getDescription() { return $this->sDescription; }
	public function getPath() { return $this->sAbsoluteDirectoryPath; }
	public function getStatus() { return $this->sStatus; }
	public function getDependencies() { return $this->aDependencies; }
	public function getCompatibleMajor() { return $this->nCompatibleMajor; }
	public function getCompatibleMinor() { return $this->nCompatibleMinor; }
	public function getCompatibleRevision() { return $this->sCompatibleRevision; }
	public function getVersionMajor() { return $this->nVersionMajor; }
	public function getVersionMinor() { return $this->nVersionMinor; }
	public function getVersionRevision() { return $this->sVersionRevision; }
	
	private function getHookFunctionName($sHookName) { return $this->getName() . '_' . $sHookName; }
	private function getLoadIndicatorFunctionName() { return '__' . $this->getName() .'_module'; }
	private function getModulePhpFileName() { return $this->getPath() . '/' . $this->getName() . self::$MODULE_SUFFIX; }
	
	/**
	 * undocumented function
	 *
	 * @return void
	 */
	private function applyInfoData() {
		$aModuleInfo = $this->readInfoFile();
		
		// set some default values
		$this->sStatus = 'disabled';
		$this->sCompatibleRevision = '';
		$this->sVersionRevision = '';
		$this->sDescription = '';
		
		// Check required entries first and return if any one of them is not satisfied
		if (!array_key_exists('enabled', $aModuleInfo)) {
			yarapi_log(
				sprintf('Module info for module [%s] does not contain the required key "enabled".', $this->sModuleName),
				PEAR_LOG_ERR
			);
			return;
		}
		
		$this->sStatus = $aModuleInfo['enabled'] === true ? 'enabled' : 'disabled';
		
		// TODO: Replace with PHP version_compare() function
		// Read the 'compatible' value if it exists and check compatibility to yarapi core
		if (array_key_exists('compatible', $aModuleInfo)) {
		  $oInstallationState = InstallationState::getInstance();
			$YARAPI_VERSION = $oInstallationState->getCoreVersion();
			
			// Parse yarapi version
			$aVersionParts = explode('.', $YARAPI_VERSION, 3);
			if (count($aVersionParts) == 3) list($nYarapiMajor, $nYarapiMinor, $nYarapiRevision) = $aVersionParts;
			else list($nYarapiMajor, $nYarapiMinor) = $aVersionParts;
			
			// now compatible value
			$aVersionParts = explode('.', $aModuleInfo['compatible'], 3);
			if (count($aVersionParts) == 3) list($this->nCompatibleMajor, $this->nCompatibleMinor, $this->sCompatibleRevision) = $aVersionParts;
			else list($this->nCompatibleMajor, $this->nCompatibleMinor) = $aVersionParts;
			
			// Check if this Module is compatible with this yarapi core and mark it incompatible or outdated otherwise
			if ($nYarapiMajor < $this->nCompatibleMajor || ($nYarapiMajor >= $this->nCompatibleMajor && $nYarapiMinor < $this->nCompatibleMinor)) {
				$this->sStatus = 'incompatible';
			} else if ($nYarapiMinor > $this->nCompatibleMinor) {
				$this->sStatus = 'outdated';
			}
		}
		
		// Parse the module version
		if (array_key_exists('compatible', $aModuleInfo)) {
			$aVersionParts = explode('.', $aModuleInfo['compatible'], 3);
			if (count($aVersionParts) == 3) list($this->nVersionMajor, $this->nVersionMinor, $this->sVersionRevision) = $aVersionParts;
			else list($this->nVersionMajor, $this->nVersionMinor) = $aVersionParts;
		}
		
		if (array_key_exists('description', $aModuleInfo)) {
			$this->sDescription = $aModuleInfo['description'];
		}
		
		if (array_key_exists('dependencies', $aModuleInfo)) {
			$this->aDependencies = $aModuleInfo['dependencies'];
		}
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 */
	private function readInfoFile() {
		$aResult = array();
		$sInfoFilePath = $this->sAbsoluteDirectoryPath . '/' . self::$INFO_NAME;
		
		// Check if file exists
		if (!file_exists($sInfoFilePath) || !is_readable($sInfoFilePath)) {
			yarapi_log(
				sprintf('Module info file (%s) for module [%s] does not exist or is not readable.', $sInfoFilePath, $this->sModuleName),
				PEAR_LOG_WARNING
			);
			return $aResult;
		}
		
		$aResult = spyc_load_file($sInfoFilePath);
		
		return $aResult;
	}
	
}

?>
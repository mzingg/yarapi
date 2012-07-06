<?php
class Modules extends PersistentState {
	
	public static function invokeHook() {
		$oModules = Modules::getInstance();
		
		$aArguments = func_get_args();
		return call_user_func_array(array($oModules, '_invokeHook'), $aArguments);		
	}
	
	public static function invokeHookModular() {
		$oModules = Modules::getInstance();
		
		$aArguments = func_get_args();
		return call_user_func_array(array($oModules, '_invokeHookModular'), $aArguments);   
	}
		
	public static function findModuleByName($sModuleName) {
    $oModules = Modules::getInstance();   
    return $oModules->_findModuleByName($sModuleName);
	}
	
	public static function findModulesByStatus($sStatus) {
		$oModules = Modules::getInstance();		
		return $oModules->_findModulesByStatus($sStatus);
	}
	
	public static function getInstance() {
		static $oRequestCachedObject = null;
		
		if (!$oRequestCachedObject) {
			$oRequestCachedObject = new Modules();
		}
		
		return $oRequestCachedObject;
	}
	
	private $oInstallationState;
  private $aModulesConfiguration;
  
  private $aModulesByName;
  private $aModulesByStatus;
  
	private function Modules() {
		$this->oInstallationState = InstallationState::getInstance();
    $this->aModulesConfiguration = yarapi_load_config('modules');
    
    if (!$this->loadFromStateIfExists()) {
		  $this->scanForModules();
		  $this->persist();
    }
	}
	
	private function loadFromStateIfExists() {
		$aData = $this->load();
		if ($aData) {
			
			$this->aModulesByName = array();			
	    $aSerializedModulesByName = $aData['modulesByName'];
	    foreach($aSerializedModulesByName as $sName => $sModuleData) {
	      $this->aModulesByName[$sName] = unserialize($sModuleData);
	    }
	    
	    $this->aModulesByStatus = array();
	    $aSerializedModulesByStatus = $aData['modulesByStatus'];
	    foreach($aSerializedModulesByStatus as $sState => $aModules) {
	      $this->aModulesByStatus[$sState] = array();
	      foreach ($aModules as $sName => $sModuleData) {
	        $this->aModulesByStatus[$sState][$sName] = unserialize($sModuleData);
	      }
	    }
			
      return true;
		}
		
		return false;
	}
	
	private function scanForModules() {
		$oInstallationState = $this->oInstallationState;
		$aModulesConfiguration = $this->aModulesConfiguration;
		
	  // Open modules directory under all tree and list the subdirectories for module names
    $sModulesDir = $oInstallationState->getInstallationRoot() . '/core/modules';
    $aFiles = scandir($sModulesDir);
    foreach ($aFiles as $sSubEntry) {
      if ($sSubEntry == '.' || $sSubEntry == '..') continue;
      
      // if the directory is one of the excluded entries do nothing
      if (in_array($sSubEntry, $aModulesConfiguration['ignored_directories'])) continue;

      $sAbsolutePath = $sModulesDir . '/' . $sSubEntry;
      if (!is_dir($sAbsolutePath)) continue;

      $oModule = new Module($sSubEntry, $sAbsolutePath);
      $this->register($oModule);
    }
    
		// Open modules directory under all tree and list the subdirectories for module names
		$sModulesDir = $oInstallationState->getSitesAllDirectoryPath() . '/modules';
 		$aFiles = scandir($sModulesDir);
		foreach ($aFiles as $sSubEntry) {
		  if ($sSubEntry == '.' || $sSubEntry == '..') continue;
		  
		  // if the directory is one of the excluded entries do nothing
		  if (in_array($sSubEntry, $aModulesConfiguration['ignored_directories'])) continue;

			$sAbsolutePath = $sModulesDir . '/' . $sSubEntry;
			if (!is_dir($sAbsolutePath)) continue;

			$oModule = new Module($sSubEntry, $sAbsolutePath);
			$this->register($oModule);
		}
		
		// now do the same with the host specific modules directory (overwriting existing modules)
		$sModulesDir = $oInstallationState->getSitesDirectoryPath(). '/' . $_SERVER['HTTP_HOST'] . '/modules';
		if (file_exists($sModulesDir) && is_readable($sModulesDir)) {
  		$aFiles = scandir($sModulesDir);
  		foreach ($aFiles as $sSubEntry) {
  		  if ($sSubEntry == '.' || $sSubEntry == '..') continue;
  
        // if the directory is one of the excluded entries do nothing
        if (in_array($sSubEntry, $aModulesConfiguration['ignored_directories'])) continue;
  		  
        $sAbsolutePath = $sModulesDir . '/' . $sSubEntry;
  			if (!is_dir($sAbsolutePath)) continue;
  
  			$oModule = new Module($sSubEntry, $sAbsolutePath);
  			$this->register($oModule);
  		}
		}
	}

  private function register(Module $oModule) {
    if (is_null($oModule)) return;

    // Initialize data structures
    if (!is_array($this->aModulesByName)) $this->aModulesByName = array();
    if (!is_array($this->aModulesByStatus)) $this->aModulesByStatus = array();
    if (!array_key_exists($oModule->getStatus(), $this->aModulesByStatus)) {
      $this->aModulesByStatus[$oModule->getStatus()] = array();
    }
    
    if (array_key_exists($oModule->getName(), $this->aModulesByName)) {
      yarapi_log(sprintf('Overwriting module %s.', $oModule->getName()), PEAR_LOG_WARNING);
    }
    
    $this->aModulesByName[$oModule->getName()] = $oModule;
    $this->aModulesByStatus[$oModule->getStatus()][$oModule->getName()] = $oModule;
  }
    
	protected function getStateIdentifier() {
    return 'modulesState';
  }
  
  protected function getStateData() {
  	
  	$aSerializedModulesByName = array();
  	foreach($this->aModulesByName as $sName => $oModule) {
  		$aSerializedModulesByName[$sName] = serialize($oModule);
  	}
  	
    $aSerializedModulesByStatus = array();
    foreach($this->aModulesByStatus as $sState => $aModules) {
    	$aSerializedModulesByStatus[$sState] = array();
    	foreach ($aModules as $sName => $oModule) {
        $aSerializedModulesByStatus[$sState][$sName] = serialize($oModule);
    	}
    }
  	
    $aData = array(
      'modulesByName' => $aSerializedModulesByName,
      'modulesByStatus' => $aSerializedModulesByStatus
    );
    
    return $aData;
  }
	
  private function _invokeHook() {
    $aArguments = func_get_args();
    $aModularResults = call_user_func_array(array($this, '_invokeHookModular'), $aArguments);
    
    $aResults = array();
    foreach ($aModularResults as $sModuleName => $result) {
      if (is_array($result)) {
        $aResults = array_merge($aResults, $result);
      } else if (!is_null($result)) {
        $aResults[] = $result;
      }
    }
    
    if (count($aResults) == 1) {
      $aResults = $aResults[0];
    } else {
      $aResults = array_unique($aResults);
    }

    return $aResults;
  }
  
  private function _invokeHookModular() {
    $aArguments = func_get_args();  
    if (is_array($aArguments[0]) && count($aArguments[0]) > 0) {
      $aModules = array_shift($aArguments);
    } else if ($aArguments[0] instanceof Module) {
      $aModules = array(array_shift($aArguments));
    } else {
      $aModules = $this->findModulesByStatus('enabled');
    }

    $aResults = array();
    foreach ($aModules as $sModuleName => $oModule) {
      $aResults[$oModule->getName()] = call_user_func_array(array($oModule, 'callHook'), $aArguments);
    }    
    
    return $aResults;
  }
    
  private function _findModuleByName($sModuleName) {
    if (!array_key_exists($sModuleName, $this->aModulesByName)) return null;
    
    return $this->aModulesByName[$sModuleName];   
  }
  
  private function _findModulesByStatus($sStatus) {
    // Check input and modules directory
    if (!in_array($sStatus, array('all', 'enabled', 'disabled', 'outdated', 'incompatible'))) {
      $sStatus = 'all';
    }
        
    if (!array_key_exists($sStatus, $this->aModulesByStatus)) return array();
    
    return $this->aModulesByStatus[$sStatus];
  }
}

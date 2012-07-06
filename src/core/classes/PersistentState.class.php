<?php

abstract class PersistentState {
	
	private $sCurrentCheckSum;
	
  protected abstract function getStateIdentifier();
  
  protected abstract function getStateData();
  
  public function persist() {
    $aData = $this->getStateData();
    $sChecksum = $this->generateChecksum($aData);
    if ($sChecksum != $this->sCurrentCheckSum) {
    	$aData['__checksum'] = $sChecksum;
      file_put_contents($this->getStateYmlFilePath(), Spyc::YAMLDump($aData, 2, 0));
    }
  }
	
  protected function load() {
    $sStateFile = $this->getStateYmlFilePath();
    
    if (!file_exists($sStateFile) || !is_readable($sStateFile)) {
      return array(); 
    }
    
    $sFileContents = file_get_contents($sStateFile);
    
    $aData = Spyc::YAMLLoadString($sFileContents); 
    $this->sCurrentCheckSum = $aData['__checksum'];
    
    return $aData;
  }
  
  private function generateChecksum($aData) {
    return md5(serialize($aData));  	
  }
  
  protected function getStateStorageDirectory() {
    $oInstallationState = InstallationState::getInstance();
    return $oInstallationState->getVarDirectoryPath();
  }  
  
  private function getStateYmlFilePath() {
    return $this->getStateStorageDirectory() . '/' . $this->getStateIdentifier() . '.yml';
  }
    
}
<?php

class Task {
	
	private $sMethod;
	private $sPath;
	private $data;
	
	private $aPathParts;
	private $aArguments;
	
	private $bHandled;
	
	public function __construct($sMethod, $sPath) {
		if (strpos($sPath, '/') === false || $sPath[0] != '/') {
			yarapi_log('Path must contain at least one / or begin with one.', PEAR_LOG_ERR);
		}
		
		$this->sMethod = strtolower($sMethod);
		$this->sPath = $sPath;
		$this->aPathParts = explode('/', substr($sPath, 1));
		
		$this->data = null;
		
		$this->aArguments = array();
		
		$this->bHandled = false;
	}
	
	public function setData($data) { $this->data = $data; }
	public function getRawData() { return $this->data; }
	public function getData() { 
		$aResult = array();
    if (is_object($this->data)) {
      $aObjectVariables = get_object_vars($this->data);
        
      if (array_key_exists('data', $aObjectVariables) && is_array($this->data->data)) {
        $aResult = $this->data->data;
      } else if (array_key_exists('data', $aObjectVariables) && is_object($this->data->data)) {
      	$aResult = get_object_vars($this->data->data);
      } else {
        foreach ($aObjectVariables as $sField => $data) {
        	$aResult[$sField] = $data;
        }
      }
    }
      
    if (is_array($this->data))
      $aResult = $this->data;
      
    return $aResult;		
	}
	
	public function getMandatoryDataValue($sValueKey) {
		$aData = $this->getData();
		if (!$aData)
		  throw new Exception(sprintf('Tried to retrieve mandatory data value [%s] but task contains no data.', $sValueKey));
		
	  if (is_array($aData) && array_key_exists($sValueKey, $aData))
	    return $aData[$sValueKey];
	    
	  throw new Exception(sprintf('Value [%s] is mandatory but was not found in task data.', $sValueKey));
		
	}
	
  public function getDataValueOrDefault($sValueKey, $sDefaultValue = null) {
    $aData = $this->getData();
    if (!$aData)
  	  return $sDefaultValue;
    
    if (is_array($aData) && array_key_exists($sValueKey, $aData))
      return $aData[$sValueKey];
      
    return $sDefaultValue;    
  }
	
	public function getMethod() { return $this->sMethod; }
	
	public function getPathCount() { return count($this->aPathParts); }
	
	public function getPathArg($index) {
		if (!is_numeric($index) || $index < 0 || $index > count($this->aPathParts) - 1) return null;
		return $this->aPathParts[$index];
	}
	
	public function getArguments() {
		return $this->aArguments;
	}
	
	public function getArgumentOrDefault($sArgumentName, $sDefaultValue) {
		if (!array_key_exists($sArgumentName, $this->aArguments)) {
			return $sDefaultValue;
		}
		
		return $this->aArguments[$sArgumentName]; 
	}
	
	public function addArgument($sArgumentName, $sArgumentValue) {
		$this->aArguments[$sArgumentName] = $sArgumentValue;
	}
	
	public function isHandled() {
	  return $this->bHandled;
	}
	
	public function setHandled() {
	  $this->bHandled = true;
	}
	
}

?>
<?php

function command_request(Task $oTask, $oResult) {
  // Only answer to /yarapi/command/<commandname>
  if ($oTask->getPathCount() < 3 || $oTask->getPathArg(0) != 'yarapi' || $oTask->getPathArg(1) != 'command') 
	return;
  
  $sCommand = $oTask->getPathArg(2);
  $aArguments = $oTask->getArguments();
  
  $oResult->command = $sCommand;
  $oResult->arguments = $aArguments; 
  $oResult->results = Modules::invokeHook('cmd_' . $sCommand, $aArguments);
  if (!$oResult->results)
	$oResult->results = Modules::invokeHook('command', $sCommand, $aArguments);
  
  $oTask->setHandled();
}
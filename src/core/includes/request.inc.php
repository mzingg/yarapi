<?php
function yarapi_get_task() {
	$sRequestMethod = strtolower($_SERVER['REQUEST_METHOD']);
	$sApplicationContext = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
	$sRequestPath = str_replace($sApplicationContext, '', $_SERVER['REQUEST_URI']);

	
	$nQuestionMarkPos = strpos($sRequestPath, '?'); 
	if ($nQuestionMarkPos !== false) {
		$sRequestPath = substr($sRequestPath, 0, $nQuestionMarkPos);
	}
	
	$oData = null;
	if ($sRequestMethod == 'put' || $sRequestMethod == 'post') {
		$sData = @file_get_contents('php://input');
		if ($sData) {
			$oData = json_decode($sData);
		}
	}

	$oTask = new Task($sRequestMethod, $sRequestPath);
	$oTask->setData($oData);
	foreach (array_keys($_REQUEST) as $sArgumentName) {
		$oTask->addArgument($sArgumentName, $_REQUEST[$sArgumentName]);
	}
	
	return $oTask;
}

/**
 * Main yarapi entry point. Takes the request, asks the modules how to handle it
 * and returns the result.
 * Important: You must not print to the output buffer before calling this method,
 *            because it needs to set certain headers.
 */
function yarapi_handle_request() {		
	$oTask = yarapi_get_task();
	$oResult = new stdClass();
	
	// First call the generic request hook
	try {
	   Modules::invokeHook('request', & $oTask, & $oResult);
	} catch (Exception $e) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Exception: " . $e->getMessage());
		return;
	}
	
	// Nobody did something to the result - so it is not there -> send 404
	if (!$oTask->isHandled()) {
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
		return;
	}
  
	header('Content-Type: application/json');	
	print json_encode($oResult);
}
?>
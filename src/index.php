<?php
// Set up the include and autoload configuration and the environment
require('core/includes/bootstrap.inc.php');

// define the global user
global $YARAPI_USER;
$YARAPI_USER = yarapi_get_user();

// Call the request handler to generate the result
yarapi_handle_request();
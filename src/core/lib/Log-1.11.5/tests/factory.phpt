--TEST--
Log: Factory
--FILE--
<?php

require_once 'Log.php';

$console1 = &Log::factory('console');
$console2 = &Log::factory('console');

if ($console1 instanceof Log_console && $console2 instanceof Log_console)
{
	echo "Two Log_console objects.\n";
}

if ($console1->_id != $console2->_id) {
	echo "The objects have different IDs.\n";
}

--EXPECT--
Two Log_console objects.
The objects have different IDs.

--TEST--
Log: Singleton
--FILE--
<?php

require_once 'Log.php';

$console1 = &Log::singleton('console');
$console2 = &Log::singleton('console');

if ($console1 instanceof Log_console && $console2 instanceof Log_console)
{
	echo "Two Log_console objects.\n";
}

if ($console1->_id == $console2->_id) {
	echo "The objects have the same ID.\n";
}

--EXPECT--
Two Log_console objects.
The objects have the same ID.

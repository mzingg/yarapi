<?php

function auth_basic_user($oUser) {
	$oUser->id = (array_key_exists('REMOTE_USER', $_SERVER) ? $_SERVER['REMOTE_USER'] : $oUser->id);
}

?>
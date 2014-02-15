<?php

function yarapi_get_user() {
   $oUser = new stdClass();
   $oUser->id = 'anonymous';
   
   Modules::invokeHook('user', $oUser);
   
   return $oUser;
}

?>
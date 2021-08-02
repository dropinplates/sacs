<?php
session_start();
include 'functions-methods.php';
$getOptions = setMetaArray(array('action'=>'logout'),'');
//$logsID = insertMetaValue("logs","(user,meta,options,ipaddress,date)","('".$_SESSION["userid"]."','user','".$getOptions."','".getIpAddress()."','".getTime('datetime')."')");
//add_log($_SESSION["userid"],'has been logged out','IP ADDRESS: '.$theIPaddress);
//unset($_SESSION["userid"]);
foreach($_SESSION as $sessionKey => $sessionValue){
	unset($_SESSION[$sessionKey]);
}
header("Location:".Info::URL);
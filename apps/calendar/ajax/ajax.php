<?php
require_once("../libs/sanitize.php");
require_once ("../../../lib/base.php");
if( !OC_USER::isLoggedIn()){
	die("nosession");
}
if(sanitize_paranoid_string($_GET["task"]) == "load_events"){
	echo "parsing_fail";
	exit;
}elseif(sanitize_paranoid_string($_GET["task"]) == "edit_event"){
	echo "edit_event";
	exit;
}elseif(sanitize_paranoid_string($_GET["task"]) == "new_event"){
	echo "new_event";
	exit;
}elseif(sanitize_paranoid_string($_GET["task"]) == "edit_settings"){
	echo "edit_settings";
	exit;
}elseif(sanitize_paranoid_string($_GET["task"]) == "choose_calendar"){
	echo "choose_calendar";
	exit;
}else{
	die("unknown task");
}
?>
<?php

OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$username = $_POST["username"];
$group = $_POST["group"];

// Toggle group
if(OC_SubAdmin::isSubAdminofGroup($username, $group)) {
	OC_SubAdmin::deleteSubAdmin($username, $group);
}else{
	OC_SubAdmin::createSubAdmin($username, $group);
}

OC_JSON::success();

<?php

OC_JSON::checkLoggedIn();
OC_JSON::callCheck();

if(isset($_POST['user'])) {
	if(isset($_POST['size'])) {
		OC_JSON::success(array('data' => \OC_Avatar::get($_POST['user'], $_POST['size'])));
	} else {
		OC_JSON::success(array('data' => \OC_Avatar::get($_POST['user'])));
	}
} else {
	OC_JSON::error();
}

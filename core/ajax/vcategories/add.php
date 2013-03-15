<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('core', 'ajax/vcategories/add.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('core', 'ajax/vcategories/add.php: '.$msg, OC_Log::DEBUG);
}

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l = OC_L10N::get('core');

$category = isset($_POST['category']) ? strip_tags($_POST['category']) : null;
$type = isset($_POST['type']) ? $_POST['type'] : null;

if(is_null($type)) {
	bailOut($l->t('Category type not provided.'));
}

if(is_null($category)) {
	bailOut($l->t('No category to add?'));
}

debug(print_r($category, true));

$categories = new OC_VCategories($type);
if($categories->hasCategory($category)) {
	bailOut($l->t('This category already exists: %s', array($category)));
} else {
	$categories->add($category, true);
}

OC_JSON::success(array('data' => array('categories'=>$categories->categories())));

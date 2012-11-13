<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('core', 'ajax/vcategories/delete.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('core', 'ajax/vcategories/delete.php: '.$msg, OC_Log::DEBUG);
}

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l = OC_L10N::get('core');

$type = isset($_POST['type']) ? $_POST['type'] : null;
$categories = isset($_POST['categories']) ? $_POST['categories'] : null;

if(is_null($type)) {
	bailOut($l->t('Object type not provided.'));
}

debug('The application using category type "'
	. $type
	. '" uses the default file for deletion. OC_VObjects will not be updated.');

if(is_null($categories)) {
	bailOut($l->t('No categories selected for deletion.'));
}

$vcategories = new OC_VCategories($type);
$vcategories->delete($categories);
OC_JSON::success(array('data' => array('categories'=>$vcategories->categories())));

<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('core', 'ajax/vcategories/addToFavorites.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('core', 'ajax/vcategories/addToFavorites.php: '.$msg, OC_Log::DEBUG);
}

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l = OC_L10N::get('core');

$id = isset($_POST['id']) ? strip_tags($_POST['id']) : null;
$type = isset($_POST['type']) ? $_POST['type'] : null;

if(is_null($type)) {
	bailOut($l->t('Object type not provided.'));
}

if(is_null($id)) {
	bailOut($l->t('%s ID not provided.', $type));
}

$categories = new OC_VCategories($type);
if(!$categories->addToFavorites($id, $type)) {
	bailOut($l->t('Error adding %s to favorites.', $id));
}

OC_JSON::success();

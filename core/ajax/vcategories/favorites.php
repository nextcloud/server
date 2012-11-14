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

$type = isset($_GET['type']) ? $_GET['type'] : null;

if(is_null($type)) {
	$l = OC_L10N::get('core');
	bailOut($l->t('Object type not provided.'));
}

$categories = new OC_VCategories($type);
$ids = $categories->getFavorites($type);

OC_JSON::success(array('ids' => $ids));

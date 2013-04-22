<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('core', 'ajax/vcategories/edit.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('core', 'ajax/vcategories/edit.php: '.$msg, OC_Log::DEBUG);
}

OC_JSON::checkLoggedIn();

$l = OC_L10N::get('core');

$type = isset($_GET['type']) ? $_GET['type'] : null;

if(is_null($type)) {
	bailOut($l->t('Category type not provided.'));
}

$tmpl = new OCP\Template("core", "edit_categories_dialog");

$vcategories = new OC_VCategories($type);
$categories = $vcategories->categories();
debug(print_r($categories, true));
$tmpl->assign('categories', $categories);
$tmpl->printpage();

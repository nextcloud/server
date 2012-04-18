<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('core','ajax/vcategories/edit.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('core','ajax/vcategories/edit.php: '.$msg, OC_Log::DEBUG);
}

require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
$app = isset($_GET['app'])?$_GET['app']:null;

if(is_null($app)) {
	bailOut('Application name not provided.');
}

OC_JSON::checkAppEnabled($app);
$tmpl = new OC_TEMPLATE("core", "edit_categories_dialog");

$vcategories = new OC_VCategories($app);
$categories = $vcategories->categories();
debug(print_r($categories, true));
$tmpl->assign('categories',$categories);
$tmpl->printpage();

?>

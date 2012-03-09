<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');
function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('contacts','ajax/categories/edit.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('contacts','ajax/categories/edit.php: '.$msg, OC_Log::DEBUG);
}

$tmpl = new OC_TEMPLATE("contacts", "part.edit_categories_dialog");

$categories = OC_Contacts_App::$categories->categories();
debug(print_r($categories, true));
$tmpl->assign('categories',$categories);
$tmpl->printpage();

?>

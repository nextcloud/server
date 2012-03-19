<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('core','ajax/vcategories/add.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('core','ajax/vcategories/add.php: '.$msg, OC_Log::DEBUG);
}

require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
$category = isset($_GET['category'])?strip_tags($_GET['category']):null;
$app = isset($_GET['app'])?$_GET['app']:null;

if(is_null($app)) {
	bailOut(OC_Contacts_App::$l10n->t('Application name not provided.'));
}

OC_JSON::checkAppEnabled($app);

if(is_null($category)) {
	bailOut(OC_Contacts_App::$l10n->t('No category to add?'));
}

debug(print_r($category, true));

$categories = new OC_VCategories($app);
if($categories->hasCategory($category)) {
	bailOut(OC_Contacts_App::$l10n->t('This category already exists: '.$category));
} else {
	$categories->add($category, true);
}

OC_JSON::success(array('data' => array('categories'=>$categories->categories())));

?>

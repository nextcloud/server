<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('core','ajax/vcategories/delete.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('core','ajax/vcategories/delete.php: '.$msg, OC_Log::DEBUG);
}

require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
$app = isset($_POST['app'])?$_POST['app']:null;
$categories = isset($_POST['categories'])?$_POST['categories']:null;
if(is_null($app)) {
	bailOut(OC_Contacts_App::$l10n->t('Application name not provided.'));
}

OC_JSON::checkAppEnabled($app);

debug('The application "'.$app.'" uses the default file. OC_VObjects will not be updated.');

if(is_null($categories)) {
	bailOut('No categories selected for deletion.');
}

$vcategories = new OC_VCategories($app);
$vcategories->delete($categories);
OC_JSON::success(array('data' => array('categories'=>$vcategories->categories())));

?>

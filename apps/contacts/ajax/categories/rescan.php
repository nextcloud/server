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

foreach ($_POST as $key=>$element) {
	debug('_POST: '.$key.'=>'.print_r($element, true));
}

function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('contacts','ajax/categories/rescan.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('contacts','ajax/categories/rescan.php: '.$msg, OC_Log::DEBUG);
}

$addressbooks = OC_Contacts_Addressbook::all(OC_User::getUser());
if(count($addressbooks) == 0) {
	bailOut(OC_Contacts_App::$l10n->t('No address books found.'));
}
$addressbookids = array();
foreach($addressbooks as $addressbook) {
	$addressbookids[] = $addressbook['id'];
} 
$contacts = OC_Contacts_VCard::all($addressbookids);
if(count($contacts) == 0) {
	bailOut(OC_Contacts_App::$l10n->t('No contacts found.'));
}

OC_Contacts_App::scanCategories($contacts);
$categories = OC_Contacts_App::getCategories();

OC_JSON::success(array('data' => array('categories'=>$categories)));

?>

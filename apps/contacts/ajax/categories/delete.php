<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');

foreach ($_POST as $key=>$element) {
	debug('_POST: '.$key.'=>'.print_r($element, true));
}

function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/categories/delete.php: '.$msg, OCP\Util::DEBUG);
	exit();
}
function debug($msg) {
	OCP\Util::writeLog('contacts','ajax/categories/delete.php: '.$msg, OCP\Util::DEBUG);
}

$categories = isset($_POST['categories'])?$_POST['categories']:null;

if(is_null($categories)) {
	bailOut(OC_Contacts_App::$l10n->t('No categories selected for deletion.'));
}

debug(print_r($categories, true));

$addressbooks = OC_Contacts_Addressbook::all(OCP\USER::getUser());
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

$cards = array();
foreach($contacts as $contact) {
	$cards[] = array($contact['id'], $contact['carddata']);
} 

debug('Before delete: '.print_r($categories, true));

$catman = new OC_VCategories('contacts');
$catman->delete($categories, $cards);
debug('After delete: '.print_r($catman->categories(), true));
OC_Contacts_VCard::updateDataByID($cards);
OCP\JSON::success(array('data' => array('categories'=>$catman->categories())));

?>

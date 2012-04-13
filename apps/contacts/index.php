<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * Copyright (c) 2011 Jakob Sack mail@jakobsack.de
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('../../lib/base.php');

// Check if we are a user
OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('contacts');

// Get active address books. This creates a default one if none exists.
$ids = OC_Contacts_Addressbook::activeIds(OC_User::getUser());
$contacts = OC_Contacts_VCard::all($ids);

$addressbooks = OC_Contacts_Addressbook::active(OC_User::getUser());

// Load the files we need
OC_App::setActiveNavigationEntry( 'contacts_index' );

// Load a specific user?
$id = isset( $_GET['id'] ) ? $_GET['id'] : null;
$details = array();

if(is_null($id) && count($contacts) > 0) {
	$id = $contacts[0]['id'];
}
if(!is_null($id)) {
	$vcard = OC_Contacts_App::getContactVCard($id);
	$details = OC_Contacts_VCard::structureContact($vcard);
}
$property_types = OC_Contacts_App::getAddPropertyOptions();
$phone_types = OC_Contacts_App::getTypesOfProperty('TEL');
$categories = OC_Contacts_App::getCategories();

$upload_max_filesize = OC_Helper::computerFileSize(ini_get('upload_max_filesize'));
$post_max_size = OC_Helper::computerFileSize(ini_get('post_max_size'));
$maxUploadFilesize = min($upload_max_filesize, $post_max_size);

$freeSpace=OC_Filesystem::free_space('/');
$freeSpace=max($freeSpace,0);
$maxUploadFilesize = min($maxUploadFilesize ,$freeSpace);

OC_Util::addScript('','jquery.multiselect');
OC_Util::addScript('','oc-vcategories');
OC_Util::addScript('contacts','contacts');
OC_Util::addScript('contacts','jquery.combobox');
OC_Util::addScript('contacts','jquery.inview');
OC_Util::addScript('contacts','jquery.Jcrop');
OC_Util::addScript('contacts','jquery.multi-autocomplete');
OC_Util::addStyle('','jquery.multiselect');
OC_Util::addStyle('contacts','jquery.combobox');
OC_Util::addStyle('contacts','jquery.Jcrop');
OC_Util::addStyle('contacts','contacts');

$tmpl = new OC_Template( "contacts", "index", "user" );
$tmpl->assign('uploadMaxFilesize', $maxUploadFilesize);
$tmpl->assign('uploadMaxHumanFilesize', OC_Helper::humanFileSize($maxUploadFilesize));
$tmpl->assign('property_types', $property_types);
$tmpl->assign('phone_types', $phone_types);
$tmpl->assign('categories', $categories);
$tmpl->assign('addressbooks', $addressbooks);
$tmpl->assign('contacts', $contacts);
$tmpl->assign('details', $details );
$tmpl->assign('id',$id);
$tmpl->printPage();

?>

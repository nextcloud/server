<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * Copyright (c) 2011 Jakob Sack mail@jakobsack.de
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


// Check if we are a user
OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('contacts');

// Get active address books. This creates a default one if none exists.
$ids = OC_Contacts_Addressbook::activeIds(OCP\USER::getUser());
$allcontacts = OC_Contacts_VCard::all($ids);
$contacts = array();
foreach($allcontacts as $contact) { // try to conserve some memory
	$contacts[] = array('id' => $contact['id'], 'addressbookid' => $contact['addressbookid'], 'fullname' => $contact['fullname']);
}
unset($allcontacts);
$addressbooks = OC_Contacts_Addressbook::active(OCP\USER::getUser());

// Load the files we need
OCP\App::setActiveNavigationEntry( 'contacts_index' );

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
$email_types = OC_Contacts_App::getTypesOfProperty('EMAIL');
$categories = OC_Contacts_App::getCategories();

$upload_max_filesize = OCP\Util::computerFileSize(ini_get('upload_max_filesize'));
$post_max_size = OCP\Util::computerFileSize(ini_get('post_max_size'));
$maxUploadFilesize = min($upload_max_filesize, $post_max_size);

$freeSpace=OC_Filesystem::free_space('/');
$freeSpace=max($freeSpace,0);
$maxUploadFilesize = min($maxUploadFilesize ,$freeSpace);

OCP\Util::addscript('','jquery.multiselect');
OCP\Util::addscript('','oc-vcategories');
OCP\Util::addscript('contacts','contacts');
OCP\Util::addscript('contacts','expanding');
OCP\Util::addscript('contacts','jquery.combobox');
OCP\Util::addscript('contacts','jquery.inview');
OCP\Util::addscript('contacts','jquery.Jcrop');
OCP\Util::addscript('contacts','jquery.multi-autocomplete');
OCP\Util::addStyle('','jquery.multiselect');
OCP\Util::addStyle('contacts','jquery.combobox');
OCP\Util::addStyle('contacts','jquery.Jcrop');
OCP\Util::addStyle('contacts','contacts');

$tmpl = new OCP\Template( "contacts", "index", "user" );
$tmpl->assign('uploadMaxFilesize', $maxUploadFilesize);
$tmpl->assign('uploadMaxHumanFilesize', OCP\Util::humanFileSize($maxUploadFilesize));
$tmpl->assign('property_types', $property_types);
$tmpl->assign('phone_types', $phone_types);
$tmpl->assign('email_types', $email_types);
$tmpl->assign('categories', $categories);
$tmpl->assign('addressbooks', $addressbooks);
$tmpl->assign('contacts', $contacts);
$tmpl->assign('details', $details );
$tmpl->assign('id',$id);
$tmpl->printPage();

?>

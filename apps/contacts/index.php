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
$has_contacts = (count(OC_Contacts_VCard::all($ids, 0, 1)) > 0 
	? true 
	: false); // just to check if there are any contacts.
if($has_contacts === false) {
	OCP\Util::writeLog('contacts', 
		'index.html: No contacts found.', 
		OCP\Util::DEBUG);
}

// Load the files we need
OCP\App::setActiveNavigationEntry('contacts_index');

// Load a specific user?
$id = isset( $_GET['id'] ) ? $_GET['id'] : null;
$property_types = OC_Contacts_App::getAddPropertyOptions();
$phone_types = OC_Contacts_App::getTypesOfProperty('TEL');
$email_types = OC_Contacts_App::getTypesOfProperty('EMAIL');
$categories = OC_Contacts_App::getCategories();

$upload_max_filesize = OCP\Util::computerFileSize(ini_get('upload_max_filesize'));
$post_max_size = OCP\Util::computerFileSize(ini_get('post_max_size'));
$maxUploadFilesize = min($upload_max_filesize, $post_max_size);

$freeSpace=OC_Filesystem::free_space('/');
$freeSpace=max($freeSpace, 0);
$maxUploadFilesize = min($maxUploadFilesize, $freeSpace);

OCP\Util::addscript('', 'jquery.multiselect');
OCP\Util::addscript('', 'oc-vcategories');
OCP\Util::addscript('contacts', 'contacts');
OCP\Util::addscript('contacts', 'expanding');
OCP\Util::addscript('contacts', 'jquery.combobox');
OCP\Util::addscript('files', 'jquery.fileupload');
OCP\Util::addscript('contacts', 'jquery.inview');
OCP\Util::addscript('contacts', 'jquery.Jcrop');
OCP\Util::addscript('contacts', 'jquery.multi-autocomplete');
OCP\Util::addStyle('', 'jquery.multiselect');
OCP\Util::addStyle('contacts', 'jquery.combobox');
OCP\Util::addStyle('contacts', 'jquery.Jcrop');
OCP\Util::addStyle('contacts', 'contacts');

$tmpl = new OCP\Template( "contacts", "index", "user" );
$tmpl->assign('uploadMaxFilesize', $maxUploadFilesize, false);
$tmpl->assign('uploadMaxHumanFilesize', 
	OCP\Util::humanFileSize($maxUploadFilesize), false);
$tmpl->assign('property_types', $property_types, false);
$tmpl->assign('phone_types', $phone_types, false);
$tmpl->assign('email_types', $email_types, false);
$tmpl->assign('categories', $categories, false);
$tmpl->assign('has_contacts', $has_contacts, false);
$tmpl->assign('id', $id, false);
$tmpl->printPage();

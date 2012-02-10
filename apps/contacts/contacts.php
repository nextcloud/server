<?php
require_once('../../lib/base.php');

// Check if we are a user
OC_Util::checkLoggedIn();
// Get active address books. This creates a default one if none exists.
$ids = OC_Contacts_Addressbook::activeIds(OC_User::getUser());
$contacts = OC_Contacts_VCard::all($ids);

$addressbooks = OC_Contacts_Addressbook::active(OC_User::getUser());

// Load the files we need
OC_App::setActiveNavigationEntry( 'contacts_index' );

// Load a specific user?
$id = isset( $_GET['id'] ) ? $_GET['id'] : null;
$details = array();

// FIXME: This cannot work..?
if(is_null($id) && count($contacts) > 0) {
	$id = $contacts[0]['id'];
}
if(!is_null($id)) {
	$vcard = OC_Contacts_App::getContactVCard($id);
	$details = OC_Contacts_VCard::structureContact($vcard);
}
$property_types = OC_Contacts_App::getAddPropertyOptions();
$phone_types = OC_Contacts_App::getTypesOfProperty('TEL');

$upload_max_filesize = OC_Helper::computerFileSize(ini_get('upload_max_filesize'));
$post_max_size = OC_Helper::computerFileSize(ini_get('post_max_size'));
$maxUploadFilesize = min($upload_max_filesize, $post_max_size);

$freeSpace=OC_Filesystem::free_space('/');
$freeSpace=max($freeSpace,0);
$maxUploadFilesize = min($maxUploadFilesize ,$freeSpace);

OC_Util::addScript('','jquery.multiselect');
//OC_Util::addScript('contacts','interface');
OC_Util::addScript('contacts','contacts');
OC_Util::addScript('contacts','jquery.combobox');
OC_Util::addScript('contacts','jquery.inview');
OC_Util::addScript('contacts','jquery.Jcrop');
OC_Util::addScript('contacts','jquery.jec-1.3.3');
OC_Util::addStyle('','jquery.multiselect');
//OC_Util::addStyle('contacts','styles');
OC_Util::addStyle('contacts','jquery.combobox');
OC_Util::addStyle('contacts','jquery.Jcrop');
OC_Util::addStyle('contacts','contacts');

$tmpl = new OC_Template( "contacts", "index2", "user" );
$tmpl->assign('uploadMaxFilesize', $maxUploadFilesize);
$tmpl->assign('uploadMaxHumanFilesize', OC_Helper::humanFileSize($maxUploadFilesize));
$tmpl->assign('property_types',$property_types);
$tmpl->assign('phone_types',$phone_types);
$tmpl->assign('addressbooks', $addressbooks);
$tmpl->assign('contacts', $contacts);
$tmpl->assign('details', $details );
$tmpl->assign('id',$id);
$tmpl->printPage();

?>

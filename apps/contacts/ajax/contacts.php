<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

function contacts_namesort($a,$b){
	return strcmp($a['fullname'],$b['fullname']);
}

require_once('../../../lib/base.php');
if(!OC_USER::isLoggedIn()) {
	die("<script type=\"text/javascript\">document.location = oc_webroot;</script>");
}
OC_JSON::checkAppEnabled('contacts');

$addressbooks = OC_Contacts_Addressbook::activeAddressbooks(OC_User::getUser());
$contacts = array();
foreach( $addressbooks as $addressbook ){
	$addressbookcontacts = OC_Contacts_VCard::all($addressbook['id']);
	foreach( $addressbookcontacts as $contact ){
		if(is_null($contact['fullname'])){
			continue;
		}
		$contacts[] = $contact;
	}
}
usort($contacts,'contacts_namesort');
$tmpl = new OC_TEMPLATE("contacts", "part.contacts");
$tmpl->assign('contacts', $contacts);
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'page' => $page )));
?>

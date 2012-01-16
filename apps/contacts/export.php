<?php
/**
 * Copyright (c) 2011-2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ("../../lib/base.php");
OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('contacts');
$book = isset($_GET['bookid']) ? $_GET['bookid'] : NULL;
$contact = isset($_GET['contactid']) ? $_GET['contactid'] : NULL;
$nl = "\n";
if(isset($book)){
	$addressbook = OC_Contacts_App::getAddressbook($book);
	if($addressbook['userid'] != OC_User::getUser()){
		OC_JSON::error();
		exit;
	}
	$cardobjects = OC_Contacts_VCard::all($book);
	header('Content-Type: text/directory');
	header('Content-Disposition: inline; filename=' . str_replace(' ', '_', $addressbook['displayname']) . '.vcf'); 

	foreach($cardobjects as $card) {
		echo $card['carddata'] . $nl;
	}
}elseif(isset($contact)){	
	$data = OC_Contacts_App::getContactObject($contact);
	$addressbookid = $data['addressbookid'];
	$addressbook = OC_Contacts_App::getAddressbook($addressbookid);
	if($addressbook['userid'] != OC_User::getUser()){
		OC_JSON::error();
		exit;
	}
	header('Content-Type: text/directory');
	header('Content-Disposition: inline; filename=' . str_replace(' ', '_', $data['fullname']) . '.vcf'); 
	echo $data['carddata'];
}
?>

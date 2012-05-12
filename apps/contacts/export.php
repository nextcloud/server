<?php
/**
 * Copyright (c) 2011-2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('contacts');
$bookid = isset($_GET['bookid']) ? $_GET['bookid'] : NULL;
$contactid = isset($_GET['contactid']) ? $_GET['contactid'] : NULL;
$nl = "\n";
if(isset($bookid)){
	$addressbook = OC_Contacts_App::getAddressbook($bookid);
	$cardobjects = OC_Contacts_VCard::all($bookid);
	header('Content-Type: text/directory');
	header('Content-Disposition: inline; filename=' . str_replace(' ', '_', $addressbook['displayname']) . '.vcf'); 

	foreach($cardobjects as $card) {
		echo $card['carddata'] . $nl;
	}
}elseif(isset($contactid)){
	$data = OC_Contacts_App::getContactObject($contactid);
	header('Content-Type: text/directory');
	header('Content-Disposition: inline; filename=' . str_replace(' ', '_', $data['fullname']) . '.vcf'); 
	echo $data['carddata'];
}
?>

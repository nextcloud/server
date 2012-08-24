<?php
/**
 * Copyright (c) 2011-2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('contacts');
$bookid = isset($_GET['bookid']) ? $_GET['bookid'] : null;
$contactid = isset($_GET['contactid']) ? $_GET['contactid'] : null;
$nl = "\n";
if(isset($bookid)) {
	$addressbook = OC_Contacts_App::getAddressbook($bookid);
	//$cardobjects = OC_Contacts_VCard::all($bookid);
	header('Content-Type: text/directory');
	header('Content-Disposition: inline; filename=' 
		. str_replace(' ', '_', $addressbook['displayname']) . '.vcf'); 

	$start = 0;
	$batchsize = OCP\Config::getUserValue(OCP\User::getUser(), 
		'contacts', 
		'export_batch_size', 20);
	while($cardobjects = OC_Contacts_VCard::all($bookid, $start, $batchsize)){
		foreach($cardobjects as $card) {
			echo $card['carddata'] . $nl;
		}
		$start += $batchsize;
	}
}elseif(isset($contactid)) {
	$data = OC_Contacts_App::getContactObject($contactid);
	header('Content-Type: text/vcard');
	header('Content-Disposition: inline; filename=' 
		. str_replace(' ', '_', $data['fullname']) . '.vcf'); 
	echo $data['carddata'];
}

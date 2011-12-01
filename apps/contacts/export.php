<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ("../../lib/base.php");
OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('contacts');
$book = isset($_GET['bookid']) ? $_GET['bookid'] : NULL;
$contact = isset($_GET['contactid']) ? $_GET['contactid'] : NULL;
if(isset($book)){
	$addressbook = OC_Contacts_Addressbook::find($book);
	if($addressbook['userid'] != OC_User::getUser()){
		OC_JSON::error();
		exit;
	}
	$cardobjects = OC_Contacts_VCard::all($book);
	header('Content-Type: text/directory');
	header('Content-Disposition: inline; filename=' . $addressbook['displayname'] . '.vcf'); 
	for($i = 0;$i <= count($cardobjects); $i++){
		echo trim($cardobjects[$i]['carddata']) . '\n';
	}
}elseif(isset($contact)){	
	$data = OC_Contacts_VCard::find($contact);
	$addressbookid = $data['addressbookid'];
	$addressbook = OC_Contacts_Addressbook::find($addressbookid);
	if($addressbook['userid'] != OC_User::getUser()){
		OC_JSON::error();
		exit;
	}
	header('Content-Type: text/directory');
	header('Content-Disposition: inline; filename=' . $data['fullname'] . '.vcf'); 
	echo $data['carddata'];
}
?>

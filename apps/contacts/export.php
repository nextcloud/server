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
$book = isset($_GET["bookid"]) ? $_GET["bookid"] : NULL;
$contact = isset($_GET["contactid"]) ? $_GET["contactid"] : NULL;
if(isset($book)){
	OC_Log::write('contacts',"book isset($book)",OC_Log::DEBUG);
	$addressbook = OC_Contacts_Addressbook::find($book);
	OC_Log::write('contacts',"Got addressbook",OC_Log::DEBUG);
	OC_Log::write('contacts',"userid: {$addressbook["userid"]}",OC_Log::DEBUG);
	if($addressbook["userid"] != OC_User::getUser()){
		OC_JSON::error();
		exit;
	}
	OC_Log::write('contacts',"User match",OC_Log::DEBUG);
	$cardobjects = OC_Contacts_VCard::all($book);
	header("Content-Type: text/directory");
	header("Content-Disposition: inline; filename=addressbook.vcf"); 
	for($i = 0;$i <= count($cardobjects); $i++){
		echo $cardobjects[$i]["carddata"] . "\n";
	}
}elseif(isset($contact)){	
	OC_Log::write('contacts',"contact isset($contact)",OC_Log::DEBUG);
	$data = OC_Contacts_VCard::find($contact);
	$addressbookid = $data["addressbookid"];
	OC_Log::write('contacts',"addressbookid: $addressbookid",OC_Log::DEBUG);
	$addressbook = OC_Contacts_Addressbook::find($addressbookid);
	if($addressbook["userid"] != OC_User::getUser()){
		OC_JSON::error();
		exit;
	}
	header("Content-Type: text/directory");
	header("Content-Disposition: inline; filename=" . $data["fullname"] . ".vcf"); 
	echo $data["carddata"];
}
?>

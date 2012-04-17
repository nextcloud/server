<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

$id = isset($_GET['id'])?$_GET['id']:null;
if(is_null($id)) {
	OC_JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('No ID provided'))));
	exit();
}
$vcard = OC_Contacts_App::getContactVCard( $id );
foreach($vcard->children as $property){
	//OC_Log::write('contacts','ajax/categories/checksumfor.php: '.$property->name, OC_Log::DEBUG);
	if($property->name == 'CATEGORIES') {
		$checksum = md5($property->serialize());
		OC_JSON::success(array('data' => array('value'=>$property->value, 'checksum'=>$checksum)));
		exit();
	}
}
OC_JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Error setting checksum.'))));
?>

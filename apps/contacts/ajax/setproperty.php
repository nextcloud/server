<?php
/**
 * ownCloud - Addressbook
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack mail@jakobsack.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Init owncloud
require_once('../../../lib/base.php');

$id = $_POST['id'];
$checksum = $_POST['checksum'];
$l10n = new OC_L10N('contacts');

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

$card = OC_Contacts_VCard::find( $id );
if( $card === false ){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('Contact could not be found.'))));
	exit();
}

$addressbook = OC_Contacts_Addressbook::find( $card['addressbookid'] );
if( $addressbook === false || $addressbook['userid'] != OC_USER::getUser()){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('This is not your contact.'))));
	exit();
}

$vcard = OC_VObject::parse($card['carddata']);
// Check if the card is valid
if(is_null($vcard)){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('vCard could not be read.'))));
	exit();
}

$line = null;
for($i=0;$i<count($vcard->children);$i++){
	if(md5($vcard->children[$i]->serialize()) == $checksum ){
		$line = $i;
	}
}
if(is_null($line)){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('Information about vCard is incorrect. Please reload the page.'))));
	exit();
}

// Set the value
$value = $_POST['value'];
if(is_array($value)){
	$value = OC_VObject::escapeSemicolons($value);
}
$vcard->children[$line]->setValue($value);

// Add parameters
$postparameters = isset($_POST['parameters'])?$_POST['parameters']:array();
if ($vcard->children[$line]->name == 'TEL' && !array_key_exists('TYPE', $postparameters)){
	$postparameters['TYPE']='';
}
for($i=0;$i<count($vcard->children[$line]->parameters);$i++){
	$name = $vcard->children[$line]->parameters[$i]->name;
	if(array_key_exists($name,$postparameters)){
		if($postparameters[$name] == '' || is_null($postparameters[$name])){
			unset($vcard->children[$line]->parameters[$i]);
		}
		else{
			unset($vcard->children[$line][$name]);
			$values = $postparameters[$name];
			if (!is_array($values)){
				$values = array($values);
			}
			foreach($values as $value){
				$vcard->children[$line]->add($name, $value);
			}
		}
		unset($postparameters[$name]);
	}
}
$missingparameters = array_keys($postparameters);
foreach($missingparameters as $i){
	if(!$postparameters[$i] == '' && !is_null($postparameters[$i])){
		$vcard->children[$line]->parameters[] = new Sabre_VObject_Parameter($i,$postparameters[$i]);
	}
}

// Do checksum and be happy
$checksum = md5($vcard->children[$line]->serialize());

OC_Contacts_VCard::edit($id,$vcard->serialize());

$adr_types = OC_Contacts_App::getTypesOfProperty($l10n, 'ADR');
$phone_types = OC_Contacts_App::getTypesOfProperty($l10n, 'TEL');

if ($vcard->children[$line]->name == 'FN'){
	$tmpl = new OC_Template('contacts','part.property.FN');
}
else{
	$tmpl = new OC_Template('contacts','part.property');
}
$tmpl->assign('adr_types',$adr_types);
$tmpl->assign('phone_types',$phone_types);
$tmpl->assign('property',OC_Contacts_VCard::structureProperty($vcard->children[$line],$line));
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'page' => $page, 'line' => $line, 'oldchecksum' => $_POST['checksum'] )));

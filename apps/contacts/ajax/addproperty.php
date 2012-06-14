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
 

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
OCP\JSON::callCheck();

function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/addproperty.php: '.$msg, OCP\Util::DEBUG);
	exit();
}

$id = isset($_POST['id'])?$_POST['id']:null;
$name = isset($_POST['name'])?$_POST['name']:null;
$value = isset($_POST['value'])?$_POST['value']:null;
$parameters = isset($_POST['parameters'])?$_POST['parameters']:array();

$vcard = OC_Contacts_App::getContactVCard($id);

if(!$name) {
	bailOut(OC_Contacts_App::$l10n->t('element name is not set.'));
}
if(!$id) {
	bailOut(OC_Contacts_App::$l10n->t('id is not set.'));
}

if(!$vcard) {
	bailOut(OC_Contacts_App::$l10n->t('Could not parse contact: ').$id);
}

if(!is_array($value)){
	$value = trim($value);
	if(!$value && in_array($name, array('TEL', 'EMAIL', 'ORG', 'BDAY', 'URL', 'NICKNAME', 'NOTE'))) {
		bailOut(OC_Contacts_App::$l10n->t('Cannot add empty property.'));
	}
} elseif($name === 'ADR') { // only add if non-empty elements.
	$empty = true;
	foreach($value as $part) {
		if(trim($part) != '') {
			$empty = false;
			break;
		}
	}
	if($empty) {
		bailOut(OC_Contacts_App::$l10n->t('At least one of the address fields has to be filled out.'));
	}
}

// Prevent setting a duplicate entry
$current = $vcard->select($name);
foreach($current as $item) {
	$tmpvalue = (is_array($value)?implode(';', $value):$value);
	if($tmpvalue == $item->value) {
		bailOut(OC_Contacts_App::$l10n->t('Trying to add duplicate property: '.$name.': '.$tmpvalue));
	}
}

if(is_array($value)) {
	ksort($value);  // NOTE: Important, otherwise the compound value will be set in the order the fields appear in the form!
	$value = array_map('strip_tags', $value);
} else {
	$value = strip_tags($value);
}

/* preprocessing value */
switch($name) {
	case 'BDAY':
		$date = New DateTime($value);
		$value = $date->format(DateTime::ATOM);
	case 'FN':
		if(!$value) {
			// create a method thats returns an alternative for FN.
			//$value = getOtherValue();
		}
	case 'N':
	case 'ORG':
	case 'NOTE':
		$value = str_replace('\n', ' \\n', $value);
		break;
	case 'NICKNAME':
		// TODO: Escape commas and semicolons.
		break;
	case 'EMAIL':
		$value = strtolower($value);
		break;
	case 'TEL':
	case 'ADR': // should I delete the property if empty or throw an error?
		break;
}

switch($name) {
	case 'NOTE':
		$vcard->setString('NOTE', $value);
		break;
	default:
		$property = $vcard->addProperty($name, $value); //, $parameters);
		break;
}

$line = count($vcard->children) - 1;

// Apparently Sabre_VObject_Parameter doesn't do well with multiple values or I don't know how to do it. Tanghus.
foreach ($parameters as $key=>$element) {
	if(is_array($element) && strtoupper($key) == 'TYPE') { 
		// NOTE: Maybe this doesn't only apply for TYPE?
		// And it probably shouldn't be done here anyways :-/
		foreach($element as $e){
			if($e != '' && !is_null($e)){
				$vcard->children[$line]->parameters[] = new Sabre_VObject_Parameter($key,$e);
			}
		}
	} else {
			$vcard->children[$line]->parameters[] = new Sabre_VObject_Parameter($key,$element);
	}
}
$checksum = md5($vcard->children[$line]->serialize());

if(!OC_Contacts_VCard::edit($id,$vcard)) {
	bailOut(OC_Contacts_App::$l10n->t('Error adding contact property: '.$name));
}

OCP\JSON::success(array('data' => array( 'checksum' => $checksum )));

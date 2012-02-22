<?php
/**
 * ownCloud - Addressbook
 *
 * @author Thomas Tanghus
 * @copyright 2012 Thomas Tanghus <thomas@tanghus.net>
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

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('contacts','ajax/saveproperty.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('contacts','ajax/saveproperty.php: '.$msg, OC_Log::DEBUG);
}
foreach ($_POST as $key=>$element) {
	debug('_POST: '.$key.'=>'.$element);
}

$id = isset($_POST['id'])?$_POST['id']:null;
$name = isset($_POST['name'])?$_POST['name']:null;
$value = isset($_POST['value'])?$_POST['value']:null;
$parameters = isset($_POST['parameters'])?$_POST['parameters']:null;
$checksum = isset($_POST['checksum'])?$_POST['checksum']:null;
// if(!is_null($parameters)) {
// 	debug('parameters: '.count($parameters));
// 	foreach($parameters as $key=>$val ) {
// 		debug('parameter: '.$key.'=>'.implode('/',$val));
// 	}
// }

if(is_array($value)){
	$value = array_map('strip_tags', $value);
	ksort($value); // NOTE: Important, otherwise the compound value will be set in the order the fields appear in the form!
	$value = OC_VObject::escapeSemicolons($value);
} else {
	$value = trim(strip_tags($value));
}
if(!$id) {
	bailOut(OC_Contacts_App::$l10n->t('id is not set.'));
}
if(!$checksum) {
	bailOut(OC_Contacts_App::$l10n->t('checksum is not set.'));
}
if(!$name) {
	bailOut(OC_Contacts_App::$l10n->t('element name is not set.'));
}

$vcard = OC_Contacts_App::getContactVCard( $id );
$line = OC_Contacts_App::getPropertyLineByChecksum($vcard, $checksum);
if(is_null($line)) {
	bailOut(OC_Contacts_App::$l10n->t('Information about vCard is incorrect. Please reload the page.'.$checksum.' "'.$line.'"'));
}
$element = $vcard->children[$line]->name;

if($element != $name) {
	bailOut(OC_Contacts_App::$l10n->t('Something went FUBAR. ').$name.' != '.$element);
}

switch($element) {
	case 'BDAY':
		$date = New DateTime($value);
		//$vcard->setDateTime('BDAY', $date, Sabre_VObject_Element_DateTime::DATE);
		$value = $date->format(DateTime::ATOM);
	case 'FN':
		if(!$value) {
			// create a method thats returns an alternative for FN.
			//$value = getOtherValue();
		}
	case 'N':
	case 'ORG':
	case 'NICKNAME':
		debug('Setting string:'.$name.' '.$value);
		$vcard->setString($name, $value);
		break;
	case 'EMAIL':
		$value = strtolower($value);
	case 'TEL':
	case 'ADR': // should I delete the property if empty or throw an error?
		debug('Setting element: (EMAIL/TEL/ADR)'.$element);
		if(!$value) {
			unset($vcard->children[$line]); // Should never happen...
		} else {
			$vcard->children[$line]->setValue($value);
			$vcard->children[$line]->parameters = array();
			if(!is_null($parameters)) {
				debug('Setting parameters: '.$parameters);
				foreach($parameters as $key => $parameter) {
					debug('Adding parameter: '.$key);
					foreach($parameter as $val) {
						debug('Adding parameter: '.$key.'=>'.$val);
						$vcard->children[$line]->add(new Sabre_VObject_Parameter($key, strtoupper($val)));
					}
				}
			}
		}
		break;
}
// Do checksum and be happy
$checksum = md5($vcard->children[$line]->serialize());
debug('New checksum: '.$checksum);

if(!OC_Contacts_VCard::edit($id,$vcard->serialize())) {
	OC_JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('Error updating contact property.'))));
	OC_Log::write('contacts','ajax/setproperty.php: Error updating contact property: '.$value, OC_Log::ERROR);
	exit();
}

//$adr_types = OC_Contacts_App::getTypesOfProperty('ADR');
//$phone_types = OC_Contacts_App::getTypesOfProperty('TEL');

OC_JSON::success(array('data' => array( 'line' => $line, 'checksum' => $checksum, 'oldchecksum' => $_POST['checksum'] )));

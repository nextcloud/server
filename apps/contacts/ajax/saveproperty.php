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
 

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
OCP\JSON::callCheck();

function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/saveproperty.php: '.$msg, OCP\Util::DEBUG);
	exit();
}
function debug($msg) {
	OCP\Util::writeLog('contacts','ajax/saveproperty.php: '.$msg, OCP\Util::DEBUG);
}
// foreach ($_POST as $key=>$element) {
// 	debug('_POST: '.$key.'=>'.print_r($element, true));
// }

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

if(!$name) {
	bailOut(OC_Contacts_App::$l10n->t('element name is not set.'));
}
if(!$id) {
	bailOut(OC_Contacts_App::$l10n->t('id is not set.'));
}
if(!$checksum) {
	bailOut(OC_Contacts_App::$l10n->t('checksum is not set.'));
}
if(is_array($value)){
	$value = array_map('strip_tags', $value);
	ksort($value); // NOTE: Important, otherwise the compound value will be set in the order the fields appear in the form!
	//if($name == 'CATEGORIES') {
	//	$value = OC_Contacts_VCard::escapeDelimiters($value, ',');
	//} else {
		$value = OC_Contacts_VCard::escapeDelimiters($value, ';');
	//}
} else {
	$value = trim(strip_tags($value));
}

$vcard = OC_Contacts_App::getContactVCard( $id );
$line = OC_Contacts_App::getPropertyLineByChecksum($vcard, $checksum);
if(is_null($line)) {
	bailOut(OC_Contacts_App::$l10n->t('Information about vCard is incorrect. Please reload the page: ').$checksum);
}
$element = $vcard->children[$line]->name;

if($element != $name) {
	bailOut(OC_Contacts_App::$l10n->t('Something went FUBAR. ').$name.' != '.$element);
}

/* preprocessing value */
switch($element) {
	case 'BDAY':
		$date = New DateTime($value);
		//$vcard->setDateTime('BDAY', $date, Sabre_VObject_Element_DateTime::DATE);
		$value = $date->format('Y-m-d');
		break;
	case 'FN':
		if(!$value) {
			// create a method thats returns an alternative for FN.
			//$value = getOtherValue();
		}
		break;
	case 'NOTE':
		$value = str_replace('\n', '\\n', $value);
		break;
	case 'EMAIL':
		$value = strtolower($value);
		break;
}

if(!$value) {
	unset($vcard->children[$line]);
	$checksum = '';
} else {
	/* setting value */
	switch($element) {
		case 'BDAY':
			// I don't use setDateTime() because that formats it as YYYYMMDD instead of YYYY-MM-DD
			// which is what the RFC recommends.
			$vcard->children[$line]->setValue($value);
			$vcard->children[$line]->parameters = array();
			$vcard->children[$line]->add(new Sabre_VObject_Parameter('VALUE', 'DATE'));
			debug('Setting value:'.$name.' '.$vcard->children[$line]);
			break;
		case 'FN':
		case 'N':
		case 'ORG':
		case 'NOTE':
		case 'NICKNAME':
			debug('Setting string:'.$name.' '.$value);
			$vcard->setString($name, $value);
			break;
		case 'CATEGORIES':
			debug('Setting string:'.$name.' '.$value);
			$vcard->children[$line]->setValue($value);
			break;
		case 'EMAIL':
		case 'TEL':
		case 'ADR': // should I delete the property if empty or throw an error?
			debug('Setting element: (EMAIL/TEL/ADR)'.$element);
			$vcard->children[$line]->setValue($value);
			$vcard->children[$line]->parameters = array();
			if(!is_null($parameters)) {
				debug('Setting parameters: '.$parameters);
				foreach($parameters as $key => $parameter) {
					debug('Adding parameter: '.$key);
					foreach($parameter as $val) {
						debug('Adding parameter: '.$key.'=>'.$val);
						$vcard->children[$line]->add(new Sabre_VObject_Parameter($key, strtoupper(strip_tags($val))));
					}
				}
			}
			break;
	}
	// Do checksum and be happy
	$checksum = md5($vcard->children[$line]->serialize());
}
//debug('New checksum: '.$checksum);

if(!OC_Contacts_VCard::edit($id,$vcard)) {
	bailOut(OC_Contacts_App::$l10n->t('Error updating contact property.'));
	exit();
}

OCP\JSON::success(array('data' => array( 'line' => $line, 'checksum' => $checksum, 'oldchecksum' => $_POST['checksum'] )));

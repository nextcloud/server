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

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

$id = $_POST['id'];
$checksum = $_POST['checksum'];

$vcard = OC_Contacts_App::getContactVCard( $id );
$line = OC_Contacts_App::getPropertyLineByChecksum($vcard, $checksum);

// Set the value
$value = $_POST['value'];
if(is_array($value)){
	ksort($value);  // NOTE: Important, otherwise the compound value will be set in the order the fields appear in the form!
	$value = array_map('strip_tags', $value);
	$value = OC_VObject::escapeSemicolons($value);
} else {
	$value = strip_tags($value);
}
OC_Log::write('contacts','ajax/setproperty.php: setting: '.$vcard->children[$line]->name.': '.$value, OC_Log::DEBUG);
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
// NOTE: This checksum is not used..?
$checksum = md5($vcard->children[$line]->serialize());

if(!OC_Contacts_VCard::edit($id,$vcard->serialize())) {
	OC_JSON::error(array('data' => array('message' => $l->t('Error updating contact property.'))));
	OC_Log::write('contacts','ajax/setproperty.php: Error updating contact property: '.$value, OC_Log::ERROR);
	exit();
}

$adr_types = OC_Contacts_App::getTypesOfProperty('ADR');
$phone_types = OC_Contacts_App::getTypesOfProperty('TEL');

if ($vcard->children[$line]->name == 'FN'){
	$tmpl = new OC_Template('contacts','part.property.FN');
}
elseif ($vcard->children[$line]->name == 'N'){
	$tmpl = new OC_Template('contacts','part.property.N');
}
else{
	$tmpl = new OC_Template('contacts','part.property');
}
$tmpl->assign('adr_types',$adr_types);
$tmpl->assign('phone_types',$phone_types);
$tmpl->assign('property',OC_Contacts_VCard::structureProperty($vcard->children[$line],$line));
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'page' => $page, 'line' => $line, 'checksum' => $checksum, 'oldchecksum' => $_POST['checksum'] )));

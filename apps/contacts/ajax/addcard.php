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
function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('contacts','ajax/addcard.php: '.$msg, OC_Log::DEBUG);
	exit();
}

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');
$l=new OC_L10N('contacts');

$aid = $_POST['id'];
$addressbook = OC_Contacts_App::getAddressbook( $aid );

$fn = trim($_POST['fn']);
$values = $_POST['value'];
$parameters = $_POST['parameters'];

$vcard = new OC_VObject('VCARD');
$vcard->setUID();

$n = isset($values['N'][0])?trim($values['N'][0]).';':';';
$n .= isset($values['N'][1])?trim($values['N'][1]).';':';';
$n .= isset($values['N'][2])?trim($values['N'][2]).';;':';;';

if(!$fn || ($n == ';;;;')) {
	bailOut('You have to enter both the extended name and the display name.');
}

$vcard->setString('N',$n);
$vcard->setString('FN',$fn);

// Data to add ...
$add = array('TEL', 'EMAIL', 'ORG');
$address = false;
for($i = 0; $i < 7; $i++){
	if( isset($values['ADR'][$i] ) && $values['ADR'][$i]) $address = true;
}
if( $address ) $add[] = 'ADR';

// Add data
foreach( $add as $propname){
	if( !( isset( $values[$propname] ) && $values[$propname] )){
		continue;
	}
	$value = $values[$propname];
	if( isset( $parameters[$propname] ) && count( $parameters[$propname] )){
		$prop_parameters = $parameters[$propname];
	} else {
		$prop_parameters = array();
	}
	if(is_array($value)){
		ksort($value); // NOTE: Important, otherwise the compound value will be set in the order the fields appear in the form!
		$value = OC_VObject::escapeSemicolons($value);
	}
	$vcard->addProperty($propname, $value); //, $prop_parameters);
	$line = count($vcard->children) - 1;
	foreach ($prop_parameters as $key=>$element) {
		if(is_array($element) && strtoupper($key) == 'TYPE') { 
			// FIXME: Maybe this doesn't only apply for TYPE?
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
}
$id = OC_Contacts_VCard::add($aid,$vcard->serialize());
if(!$id) {
	OC_JSON::error(array('data' => array('message' => $l->t('There was an error adding the contact.'))));
	OC_Log::write('contacts','ajax/addcard.php: Recieved non-positive ID on adding card: '.$id, OC_Log::ERROR);
	exit();
}

// NOTE: Why is this in OC_Contacts_App?
OC_Contacts_App::renderDetails($id, $vcard);

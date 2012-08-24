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

require_once __DIR__.'/../loghandler.php';

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');

$id = isset($_GET['id'])?$_GET['id']:null;
if(is_null($id)) {
	bailOut(OC_Contacts_App::$l10n->t('Missing ID'));
}
$card = OC_Contacts_VCard::find($id);
$vcard = OC_VObject::parse($card['carddata']);
if(is_null($vcard)) {
	bailOut(OC_Contacts_App::$l10n->t('Error parsing VCard for ID: "'.$id.'"'));
}
$details = OC_Contacts_VCard::structureContact($vcard);

// Make up for not supporting the 'N' field in earlier version.
if(!isset($details['N'])) {
	$details['N'] = array();
	$details['N'][0] = array($details['FN'][0]['value'],'','','','');
}

// Don't wanna transfer the photo in a json string.
if(isset($details['PHOTO'])) {
	$details['PHOTO'] = true;
	//unset($details['PHOTO']);
} else {
	$details['PHOTO'] = false;
}
$lastmodified = OC_Contacts_App::lastModified($vcard);
if(!$lastmodified) {
	$lastmodified = new DateTime();
}
$details['id'] = $id;
$details['displayname'] = $card['fullname'];
$details['addressbookid'] = $card['addressbookid'];
$details['lastmodified'] = $lastmodified->format('U');
OC_Contacts_App::setLastModifiedHeader($vcard);
OCP\JSON::success(array('data' => $details));

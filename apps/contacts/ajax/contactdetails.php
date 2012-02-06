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
function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('contacts','ajax/contactdetails.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('contacts','ajax/contactdetails.php: '.$msg, OC_Log::DEBUG);
}

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');
$l=new OC_L10N('contacts');

$id = $_GET['id'];
$vcard = OC_Contacts_App::getContactVCard( $id );
if(is_null($vcard)) {
	bailOut($l->t('Error parsing VCard for ID: "'.$id.'"'));
}
$details = OC_Contacts_VCard::structureContact($vcard);
if(isset($details['PHOTO'])) {
	$details['PHOTO'] = true;
	//unset($details['PHOTO']);
} else {
	$details['PHOTO'] = false;
}
$details['id'] = $id;

OC_JSON::success(array('data' => $details));
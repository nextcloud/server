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
$l10n = new OC_L10N('contacts');

$id = $_GET['id'];
$checksum = $_GET['checksum'];

$vcard = OC_Contacts_App::getContactVCard( $id );
$line = OC_Contacts_App::getPropertyLineByChecksum($vcard, $checksum);
if(is_null($line)){
	$l=new OC_L10N('contacts');
	OC_JSON::error(array('data' => array( 'message' => $l->t('Information about vCard is incorrect. Please reload the page.'))));
	exit();
}

unset($vcard->children[$line]);

if(!OC_Contacts_VCard::edit($id,$vcard->serialize())) {
	OC_JSON::error(array('data' => array('message' => $l->t('Error deleting contact property.'))));
	OC_Log::write('contacts','ajax/deleteproperty.php: Error deleting contact property', OC_Log::ERROR);
	exit();
}

OC_JSON::success(array('data' => array( 'id' => $id )));

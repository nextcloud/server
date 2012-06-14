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
 
function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/addcontact.php: '.$msg, OCP\Util::DEBUG);
	exit();
}

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');
OCP\JSON::callCheck();

$aid = isset($_POST['aid'])?$_POST['aid']:null;
if(!$aid) {
	$aid = min(OC_Contacts_Addressbook::activeIds()); // first active addressbook.
}
OC_Contacts_App::getAddressbook( $aid ); // is owner access check

$isnew = isset($_POST['isnew'])?$_POST['isnew']:false;
$fn = trim($_POST['fn']);
$n = trim($_POST['n']);

$vcard = new OC_VObject('VCARD');
$vcard->setUID();
$vcard->setString('FN',$fn);
$vcard->setString('N',$n);

$id = OC_Contacts_VCard::add($aid,$vcard, null, $isnew);
if(!$id) {
	OCP\JSON::error(array('data' => array('message' => OC_Contacts_App::$l10n->t('There was an error adding the contact.'))));
	OCP\Util::writeLog('contacts','ajax/addcontact.php: Recieved non-positive ID on adding card: '.$id, OCP\Util::ERROR);
	exit();
}

OCP\JSON::success(array('data' => array( 'id' => $id )));

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

$id = $_GET['id'];
$checksum = $_GET['checksum'];

$vcard = OC_Contacts_App::getContactVCard( $id );

$line = OC_Contacts_App::getPropertyLineByChecksum($vcard, $checksum);
if(is_null($line)){
	$l=new OC_L10N('contacts');
	OC_JSON::error(array('data' => array( 'message' => $l->t('Information about vCard is incorrect. Please reload the page.'))));
	exit();
}

$adr_types = OC_Contacts_App::getTypesOfProperty('ADR');
$phone_types = OC_Contacts_App::getTypesOfProperty('TEL');

$tmpl = new OC_Template('contacts','part.setpropertyform');
$tmpl->assign('id',$id);
$tmpl->assign('checksum',$checksum);
$tmpl->assign('property',OC_Contacts_VCard::structureProperty($vcard->children[$line]));
$tmpl->assign('adr_types',$adr_types);
$tmpl->assign('phone_types',$phone_types);
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'page' => $page )));

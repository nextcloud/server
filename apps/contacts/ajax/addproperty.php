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

$id = $_POST['id'];
$l10n = new OC_L10N('contacts');

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

$card = OC_Contacts_VCard::find( $id );
if( $card === false ){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('Contact could not be found.'))));
	exit();
}

$addressbook = OC_Contacts_Addressbook::find( $card['addressbookid'] );
if( $addressbook === false || $addressbook['userid'] != OC_USER::getUser()){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('This is not your contact.'))));
	exit();
}

$vcard = OC_Contacts_VCard::parse($card['carddata']);
// Check if the card is valid
if(is_null($vcard)){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('vCard could not be read.'))));
	exit();
}

$name = $_POST['name'];
$value = $_POST['value'];
$parameters = isset($_POST['parameteres'])?$_POST['parameters']:array();

OC_Contacts_VCard::addVCardProperty($vcard, $name, $value, $parameters);

$line = count($vcard->children) - 1;
$checksum = md5($property->serialize());

OC_Contacts_VCard::edit($id,$vcard->serialize());

$tmpl = new OC_Template('contacts','part.property');
$tmpl->assign('property',OC_Contacts_VCard::structureProperty($property,$line));
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'page' => $page )));

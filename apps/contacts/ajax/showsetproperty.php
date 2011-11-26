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

$id = $_GET['id'];
$checksum = $_GET['checksum'];
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

$line = null;
for($i=0;$i<count($vcard->children);$i++){
	if(md5($vcard->children[$i]->serialize()) == $checksum ){
		$line = $i;
	}
}
if(is_null($line)){
	OC_JSON::error(array('data' => array( 'message' => $l10n->t('Information about vCard is incorrect. Please reload the page.'))));
	exit();
}

$adr_types = OC_Contacts_VCard::getTypesOfProperty($l10n, 'ADR');

$tmpl = new OC_Template('contacts','part.setpropertyform');
$tmpl->assign('id',$id);
$tmpl->assign('checksum',$checksum);
$tmpl->assign('property',OC_Contacts_VCard::structureProperty($vcard->children[$line]));
$tmpl->assign('adr_types',$adr_types);
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'page' => $page )));

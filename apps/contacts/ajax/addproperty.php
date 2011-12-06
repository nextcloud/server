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
$vcard = OC_Contacts_App::getContactVCard( $id );

$name = $_POST['name'];
$value = $_POST['value'];
$parameters = isset($_POST['parameteres'])?$_POST['parameters']:array();

$property = $vcard->addProperty($name, $value, $parameters);

$line = count($vcard->children) - 1;

OC_Contacts_VCard::edit($id,$vcard->serialize());

$tmpl = new OC_Template('contacts','part.property');
$tmpl->assign('property',OC_Contacts_VCard::structureProperty($property,$line));
$page = $tmpl->fetchPage();

OC_JSON::success(array('data' => array( 'page' => $page )));

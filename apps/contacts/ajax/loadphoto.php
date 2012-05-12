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
 
// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');

// foreach ($_POST as $key=>$element) {
// 	OCP\Util::writeLog('contacts','ajax/savecrop.php: '.$key.'=>'.$element, OCP\Util::DEBUG);
// }

function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('contacts','ajax/loadphoto.php: '.$msg, OCP\Util::DEBUG);
	exit();
}

$image = null;

$id = isset($_GET['id']) ? $_GET['id'] : '';
$refresh = isset($_GET['refresh']) ? true : false;

if($id == '') {
	bailOut(OC_Contacts_App::$l10n->t('Missing contact id.'));
}

$checksum = '';
$vcard = OC_Contacts_App::getContactVCard( $id );
foreach($vcard->children as $property){
	if($property->name == 'PHOTO') {
		$checksum = md5($property->serialize());
		break;
	}
}

$tmpl = new OCP\Template("contacts", "part.contactphoto");
$tmpl->assign('id', $id);
if($refresh) {
	$tmpl->assign('refresh', 1);
}
$page = $tmpl->fetchPage();
OCP\JSON::success(array('data' => array('page'=>$page, 'checksum'=>$checksum)));
?>

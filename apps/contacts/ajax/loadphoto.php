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
 * TODO: Translatable strings.
 *       Remember to delete tmp file at some point.
 */
// Init owncloud
require_once('../../../lib/base.php');
// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

// foreach ($_POST as $key=>$element) {
// 	OC_Log::write('contacts','ajax/savecrop.php: '.$key.'=>'.$element, OC_Log::DEBUG);
// }

function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('contacts','ajax/savecrop.php: '.$msg, OC_Log::DEBUG);
	exit();
}

$image = null;

$id = isset($_GET['id']) ? $_GET['id'] : '';

if($id == '') {
	bailOut('Missing contact id.');
}

$tmpl = new OC_TEMPLATE("contacts", "part.contactphoto");
$tmpl->assign('id', $id);
$page = $tmpl->fetchPage();
OC_JSON::success(array('data' => array('page'=>$page)));
?>

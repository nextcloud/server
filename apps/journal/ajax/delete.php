<?php
/**
 * ownCloud - Journal
 *
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

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('journal');
OCP\JSON::callCheck();

$id = isset($_POST['id'])?$_POST['id']:null;
if(is_null($id)) {
	OCP\JSON::error(array('data'=>array('message' => OC_Journal_App::$l10n->t('ID is not set!'))));
	exit;
}
$journal = OC_Calendar_App::getEventObject($id);
if($journal) {
	OC_Calendar_Object::delete($id);
	OCP\JSON::success(array('data' => array( 'id' => $id )));
} else {
	OCP\JSON::error(array('data' => array('id' => $id, 'message' => OC_Journal_App::$l10n->t('Could not find journal entry: '.$id))));
}
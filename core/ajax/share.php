<?php
/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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
*/
require_once '../../lib/base.php';

OC_JSON::checkLoggedIn();
if (isset($_POST['action'])) {
	switch ($_POST['action']) {
		case 'share':
			$return = OCP\Share::share($_POST['itemType'], $_POST['item'], $_POST['shareType'], $_POST['shareWith'], $_POST['permissions']);
			// TODO May need to return private link
			($return) ? OC_JSON::success() : OC_JSON::error();
			break;
		case 'unshare':
			$return = OCP\Share::unshare($_POST['itemType'], $_POST['item'], $_POST['shareType'], $_POST['shareWith']);
			($return) ? OC_JSON::success() : OC_JSON::error();
			break;
		case 'setTarget':
			$return = OCP\Share::setTarget($_POST['itemType'], $_POST['item'], $_POST['newTarget']);
			($return) ? OC_JSON::success() : OC_JSON::error();
			break;
		case 'setPermissions':
			$return = OCP\Share::setPermissions($_POST['itemType'], $_POST['item'], $_POST['shareType'], $_POST['shareWith'], $_POST['permissions']);
			($return) ? OC_JSON::success() : OC_JSON::error();
			break;
	}
} else if (isset($_GET['fetch'])) {
	switch ($_GET['fetch']) {
		case 'getItemsSharedStatuses':
			$return = OCP\Share::getItemsShared($_GET['itemType'], OCP\Share::FORMAT_STATUSES);
			($return) ? OC_JSON::success(array('data' => $return)) : OC_JSON::error();
			break;
		case 'getItem':
			// TODO Check if the item was shared to the current user
			$return = OCP\Share::getItemShared($_GET['itemType'], $_GET['item']);
			($return) ? OC_JSON::success(array('data' => $return)) : OC_JSON::error();
			break;
		case 'getShareWith':
			// TODO Autocomplete for all users, groups, etc.
			break;
	}
}

?>
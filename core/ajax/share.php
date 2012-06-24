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

OCP\JSON::checkLoggedIn();
if (isset($_POST['action'])) {
	switch ($_POST['action']) {
		case 'share':
			$return = OCP\Share::share($_POST['itemType'], $_POST['item'], $_POST['shareType'], $_POST['shareWith'], $_POST['permissions']);
			// TODO May need to return private link
			($return) ? OCP\JSON::success() : OCP\JSON::error();
			break;
		case 'unshare':
			$return = OCP\Share::unshare($_POST['itemType'], $_POST['item'], $_POST['shareType'], $_POST['shareWith']);
			($return) ? OCP\JSON::success() : OCP\JSON::error();
			break;
		case 'setTarget':
			$return = OCP\Share::setTarget($_POST['itemType'], $_POST['item'], $_POST['newTarget']);
			($return) ? OCP\JSON::success() : OCP\JSON::error();
			break;
		case 'setPermissions':
			$return = OCP\Share::setPermissions($_POST['itemType'], $_POST['item'], $_POST['shareType'], $_POST['shareWith'], $_POST['permissions']);
			($return) ? OCP\JSON::success() : OCP\JSON::error();
			break;
	}
} else if (isset($_GET['fetch'])) {
	switch ($_GET['fetch']) {
		case 'getItemsSharedStatuses':
			$return = OCP\Share::getItemsSharedStatuses($_POST['itemType']);
			($return) ? OCP\JSON::success(array('data' => $return)) : OCP\JSON::error();
			break;
		case 'getItemShared':
			$return = OCP\Share::getItemShared($_POST['itemType'], $_POST['item']);
			($return) ? OCP\JSON::success(array('data' => $return)) : OCP\JSON::error();
			break;
		case 'getShareWith':
			// TODO Autocomplete for all users, groups, etc.
			break;
	}
}

?>
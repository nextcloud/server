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
if (isset($_POST['action']) && isset($_POST['itemType']) && isset($_POST['item'])) {
	$itemType = OCP\Util::sanitizeHTML($_POST['itemType']);
	$item = OCP\Util::sanitizeHTML($_POST['item']);
	switch ($_POST['action']) {
		case 'share':
			error_log($_POST['item']);
			if (isset($_POST['shareType']) && isset($_POST['shareWith']) && isset($_POST['permissions'])) {
				$shareType = OCP\Util::sanitizeHTML($_POST['shareType']);
				$shareWith = OCP\Util::sanitizeHTML($_POST['shareWith']);
				$permissions = OCP\Util::sanitizeHTML($_POST['permissions']);
				$return = OCP\Share::share($itemType, $item, $shareType, $shareWith, $permissions);
				// TODO May need to return private link
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
		case 'unshare':
			if (isset($_POST['shareType']) && isset($_POST['shareWith'])) {
				$shareType = OCP\Util::sanitizeHTML($_POST['shareType']);
				$shareWith = OCP\Util::sanitizeHTML($_POST['shareWith']);
				$return = OCP\Share::unshare($itemType, $item, $shareType, $shareWith);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
		case 'setTarget':
			if (isset($_POST['newTarget'])) {
				$newTarget = OCP\Util::sanitizeHTML($_POST['newTarget']);
				$return = OCP\Share::setTarget($itemType, $item, $newTarget);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
		case 'setPermissions':
			if (isset($_POST['shareType']) && isset($_POST['shareWith']) && isset($_POST['permissions'])) {
				$shareType = OCP\Util::sanitizeHTML($_POST['shareType']);
				$shareWith = OCP\Util::sanitizeHTML($_POST['shareWith']);
				$permissions = OCP\Util::sanitizeHTML($_POST['permissions']);
				$return = OCP\Share::setPermissions($itemType, $item, $shareType, $shareWith, $permissions);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
	}
} else if (isset($_GET['fetch']) && isset($_GET['itemType'])) {
	$itemType = OCP\Util::sanitizeHTML($_GET['itemType']);
	switch ($_GET['fetch']) {
		case 'getItemsSharedStatuses':
			$return = OCP\Share::getItemsShared($itemType, OCP\Share::FORMAT_STATUSES);
			($return) ? OC_JSON::success(array('data' => $return)) : OC_JSON::error();
			break;
		case 'getItem':
			// TODO Check if the item was shared to the current user
			if (isset($_GET['item'])) {
				$item = OCP\Util::sanitizeHTML($_GET['item']);
				$return = OCP\Share::getItemShared($itemType, $item);
				($return) ? OC_JSON::success(array('data' => $return)) : OC_JSON::error();
			}
			break;
		case 'getShareWith':
			// TODO Autocomplete for all users, groups, etc.
			break;
	}
}

?>
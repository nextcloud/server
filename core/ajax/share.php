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
	switch ($_POST['action']) {
		case 'share':
			if (isset($_POST['shareType']) && isset($_POST['shareWith']) && isset($_POST['permissions'])) {
				try {
					OCP\Share::share($_POST['itemType'], $_POST['item'], $_POST['shareType'], $_POST['shareWith'], $_POST['permissions']);
					// TODO May need to return private link
					OC_JSON::success();
				} catch (Exception $exception) {
					OC_JSON::error(array('data' => array('message' => $exception->getMessage())));
				}
			}
			break;
		case 'unshare':
			if (isset($_POST['shareType']) && isset($_POST['shareWith'])) {
				$return = OCP\Share::unshare($_POST['itemType'], $_POST['item'], $_POST['shareType'], $_POST['shareWith']);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
		case 'setTarget':
			if (isset($_POST['newTarget'])) {
				$return = OCP\Share::setTarget($_POST['itemType'], $_POST['item'], $_POST['newTarget']);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
		case 'setPermissions':
			if (isset($_POST['shareType']) && isset($_POST['shareWith']) && isset($_POST['permissions'])) {
				$return = OCP\Share::setPermissions($_POST['itemType'], $_POST['item'], $_POST['shareType'], $_POST['shareWith'], $_POST['permissions']);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
	}
} else if (isset($_GET['fetch'])) {
	switch ($_GET['fetch']) {
		case 'getItemsSharedStatuses':
			if (isset($_GET['itemType'])) {
				$return = OCP\Share::getItemsShared($_GET['itemType'], OCP\Share::FORMAT_STATUSES);
				($return) ? OC_JSON::success(array('data' => $return)) : OC_JSON::error();
			}
			break;
		case 'getItem':
			// TODO Check if the item was shared to the current user
			if (isset($_GET['itemType']) && isset($_GET['item'])) {
				$return = OCP\Share::getItemShared($_GET['itemType'], $_GET['item']);
				($return) ? OC_JSON::success(array('data' => $return)) : OC_JSON::error();
			}
			break;
		case 'getShareWith':
			if (isset($_GET['search'])) {
				$shareWith = array();
				if (OC_App::isEnabled('contacts')) {
					// TODO Add function to contacts to only get the 'fullname' column to improve performance
					$ids = OC_Contacts_Addressbook::activeIds();
					foreach ($ids as $id) {
						$vcards = OC_Contacts_VCard::all($id);
						foreach ($vcards as $vcard) {
							$contact = $vcard['fullname'];
							if (stripos($contact, $_GET['search']) !== false && (!isset($_GET['itemShares'][OCP\Share::SHARE_TYPE_CONTACT]) || !in_array($contact, $_GET['itemShares'][OCP\Share::SHARE_TYPE_CONTACT]))) {
								$shareWith[] = array('label' => $contact, 'value' => array('shareType' => 5, 'shareWith' => $vcard['id']));
							}
						}
					}
				}
				$count = 0;
				$users = array();
				$limit = 0;
				$offset = 0;
				while ($count < 4 && count($users) == $limit) {
					$limit = 4 - $count;
					$users = OC_User::getUsers($_GET['search'], $limit, $offset);
					$offset += $limit;
					foreach ($users as $user) {
						if ((!isset($_GET['itemShares'][OCP\Share::SHARE_TYPE_USER]) || !in_array($user, $_GET['itemShares'][OCP\Share::SHARE_TYPE_USER])) && $user != OC_User::getUser()) {
							$shareWith[] = array('label' => $user, 'value' => array('shareType' => OCP\Share::SHARE_TYPE_USER, 'shareWith' => $user));
							$count++;
						}
					}
				}
				$count = 0;
				$groups = array();
				$limit = 0;
				$offset = 0;
				while ($count < 4 && count($groups) == $limit) {
					$limit = 4 - $count;
					$groups = OC_Group::getGroups($_GET['search'], $limit, $offset);
					$offset += $limit;
					foreach ($groups as $group) {
						if (!isset($_GET['itemShares'][OCP\Share::SHARE_TYPE_GROUP]) || !in_array($group, $_GET['itemShares'][OCP\Share::SHARE_TYPE_GROUP])) {
							$shareWith[] = array('label' => $group.' (group)', 'value' => array('shareType' => OCP\Share::SHARE_TYPE_GROUP, 'shareWith' => $group));
							$count++;
						}
					}
				}
				OC_JSON::success(array('data' => $shareWith));
			}
			break;
	}
}

?>
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

OC_JSON::checkLoggedIn();
OCP\JSON::callCheck();
OC_App::loadApps();

if (isset($_POST['action']) && isset($_POST['itemType']) && isset($_POST['itemSource'])) {
	switch ($_POST['action']) {
		case 'share':
			if (isset($_POST['shareType']) && isset($_POST['shareWith']) && isset($_POST['permissions'])) {
				try {
					$shareType = (int)$_POST['shareType'];
					$shareWith = $_POST['shareWith'];
					if ($shareType === OCP\Share::SHARE_TYPE_LINK && $shareWith == '') {
						$shareWith = null;
					}
					
					$token = OCP\Share::shareItem(
						$_POST['itemType'],
						$_POST['itemSource'],
						$shareType,
						$shareWith,
						$_POST['permissions']
					);
					
					if (is_string($token)) {
						OC_JSON::success(array('data' => array('token' => $token)));
					} else {
						OC_JSON::success();
					}
				} catch (Exception $exception) {
					OC_JSON::error(array('data' => array('message' => $exception->getMessage())));
				}
			}
			break;
		case 'unshare':
			if (isset($_POST['shareType']) && isset($_POST['shareWith'])) {
				if ((int)$_POST['shareType'] === OCP\Share::SHARE_TYPE_LINK && $_POST['shareWith'] == '') {
					$shareWith = null;
				} else {
					$shareWith = $_POST['shareWith'];
				}
				$return = OCP\Share::unshare($_POST['itemType'], $_POST['itemSource'], $_POST['shareType'], $shareWith);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
		case 'setPermissions':
			if (isset($_POST['shareType']) && isset($_POST['shareWith']) && isset($_POST['permissions'])) {
				$return = OCP\Share::setPermissions(
					$_POST['itemType'],
					$_POST['itemSource'],
					$_POST['shareType'],
					$_POST['shareWith'],
					$_POST['permissions']
				);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
		case 'setExpirationDate':
			if (isset($_POST['date'])) {
				$return = OCP\Share::setExpirationDate($_POST['itemType'], $_POST['itemSource'], $_POST['date']);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
		case 'email':
			// read post variables
			$user = OCP\USER::getUser();
			$displayName = OCP\User::getDisplayName();
			$type = $_POST['itemType'];
			$link = $_POST['link'];
			$file = $_POST['file'];
			$to_address = $_POST['toaddress'];

			// enable l10n support
			$l = OC_L10N::get('core');

			// setup the email
			$subject = (string)$l->t('User %s shared a file with you', $displayName);
			if ($type === 'folder')
				$subject = (string)$l->t('User %s shared a folder with you', $displayName);

			$text = (string)$l->t('User %s shared the file "%s" with you. It is available for download here: %s',
				array($displayName, $file, $link));
			if ($type === 'folder')
				$text = (string)$l->t('User %s shared the folder "%s" with you. It is available for download here: %s',
					array($displayName, $file, $link));


			$default_from = OCP\Util::getDefaultEmailAddress('sharing-noreply');
			$from_address = OCP\Config::getUserValue($user, 'settings', 'email', $default_from );

			// send it out now
			try {
				OCP\Util::sendMail($to_address, $to_address, $subject, $text, $from_address, $displayName);
				OCP\JSON::success();
			} catch (Exception $exception) {
				OCP\JSON::error(array('data' => array('message' => OC_Util::sanitizeHTML($exception->getMessage()))));
			}
			break;
	}
} else if (isset($_GET['fetch'])) {
	switch ($_GET['fetch']) {
		case 'getItemsSharedStatuses':
			if (isset($_GET['itemType'])) {
				$return = OCP\Share::getItemsShared($_GET['itemType'], OCP\Share::FORMAT_STATUSES);
				is_array($return) ? OC_JSON::success(array('data' => $return)) : OC_JSON::error();
			}
			break;
		case 'getItem':
			if (isset($_GET['itemType'])
				&& isset($_GET['itemSource'])
				&& isset($_GET['checkReshare'])
				&& isset($_GET['checkShares'])) {
				if ($_GET['checkReshare'] == 'true') {
					$reshare = OCP\Share::getItemSharedWithBySource(
						$_GET['itemType'],
						$_GET['itemSource'],
						OCP\Share::FORMAT_NONE,
						null,
						true
					);
				} else {
					$reshare = false;
				}
				if ($_GET['checkShares'] == 'true') {
					$shares = OCP\Share::getItemShared(
						$_GET['itemType'],
						$_GET['itemSource'],
						OCP\Share::FORMAT_NONE,
						null,
						true
					);
				} else {
					$shares = false;
				}
				OC_JSON::success(array('data' => array('reshare' => $reshare, 'shares' => $shares)));
			}
			break;
		case 'getShareWith':
			if (isset($_GET['search'])) {
				$sharePolicy = OC_Appconfig::getValue('core', 'shareapi_share_policy', 'global');
				$shareWith = array();
// 				if (OC_App::isEnabled('contacts')) {
// 					// TODO Add function to contacts to only get the 'fullname' column to improve performance
// 					$ids = OC_Contacts_Addressbook::activeIds();
// 					foreach ($ids as $id) {
// 						$vcards = OC_Contacts_VCard::all($id);
// 						foreach ($vcards as $vcard) {
// 							$contact = $vcard['fullname'];
// 							if (stripos($contact, $_GET['search']) !== false
// 								&& (!isset($_GET['itemShares'])
// 								|| !isset($_GET['itemShares'][OCP\Share::SHARE_TYPE_CONTACT])
// 								|| !is_array($_GET['itemShares'][OCP\Share::SHARE_TYPE_CONTACT])
// 								|| !in_array($contact, $_GET['itemShares'][OCP\Share::SHARE_TYPE_CONTACT]))) {
// 								$shareWith[] = array('label' => $contact, 'value' => array('shareType' => 5, 'shareWith' => $vcard['id']));
// 							}
// 						}
// 					}
// 				}
				if ($sharePolicy == 'groups_only') {
					$groups = OC_Group::getUserGroups(OC_User::getUser());
				} else {
					$groups = OC_Group::getGroups();
				}
				$count = 0;
				$users = array();
				$limit = 0;
				$offset = 0;
				while ($count < 15 && count($users) == $limit) {
					$limit = 15 - $count;
					if ($sharePolicy == 'groups_only') {
						$users = OC_Group::DisplayNamesInGroups($groups, $_GET['search'], $limit, $offset);
					} else {
						$users = OC_User::getDisplayNames($_GET['search'], $limit, $offset);
					}
					$offset += $limit;
					foreach ($users as $uid => $displayName) {
						if ((!isset($_GET['itemShares'])
							|| !is_array($_GET['itemShares'][OCP\Share::SHARE_TYPE_USER])
							|| !in_array($uid, $_GET['itemShares'][OCP\Share::SHARE_TYPE_USER]))
							&& $uid != OC_User::getUser()) {
							$shareWith[] = array(
								'label' => $displayName,
								'value' => array('shareType' => OCP\Share::SHARE_TYPE_USER,
								'shareWith' => $uid)
							);
							$count++;
						}
					}
				}
				$count = 0;
				foreach ($groups as $group) {
					if ($count < 15) {
						if (stripos($group, $_GET['search']) !== false
							&& (!isset($_GET['itemShares'])
							|| !isset($_GET['itemShares'][OCP\Share::SHARE_TYPE_GROUP])
							|| !is_array($_GET['itemShares'][OCP\Share::SHARE_TYPE_GROUP])
							|| !in_array($group, $_GET['itemShares'][OCP\Share::SHARE_TYPE_GROUP]))) {
							$shareWith[] = array(
								'label' => $group.' (group)',
								'value' => array(
									'shareType' => OCP\Share::SHARE_TYPE_GROUP,
									'shareWith' => $group
								)
							);
							$count++;
						}
					} else {
						break;
					}
				}
				OC_JSON::success(array('data' => $shareWith));
			}
			break;
	}
}

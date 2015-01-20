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

$defaults = new \OCP\Defaults();

if (isset($_POST['action']) && isset($_POST['itemType']) && isset($_POST['itemSource'])) {
	switch ($_POST['action']) {
		case 'share':
			if (isset($_POST['shareType']) && isset($_POST['shareWith']) && isset($_POST['permissions'])) {
				try {
					$shareType = (int)$_POST['shareType'];
					$shareWith = $_POST['shareWith'];
					$itemSourceName = isset($_POST['itemSourceName']) ? $_POST['itemSourceName'] : null;
					if ($shareType === OCP\Share::SHARE_TYPE_LINK && $shareWith == '') {
						$shareWith = null;
					}
 					$itemSourceName=(isset($_POST['itemSourceName'])) ? $_POST['itemSourceName']:'';

					$token = OCP\Share::shareItem(
						$_POST['itemType'],
						$_POST['itemSource'],
						$shareType,
						$shareWith,
						$_POST['permissions'],
						$itemSourceName,
						(!empty($_POST['expirationDate']) ? new \DateTime($_POST['expirationDate']) : null)
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
					(int)$_POST['shareType'],
					$_POST['shareWith'],
					(int)$_POST['permissions']
				);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
		case 'setExpirationDate':
			if (isset($_POST['date'])) {
				try {
					$return = OCP\Share::setExpirationDate($_POST['itemType'], $_POST['itemSource'], $_POST['date']);
					($return) ? OC_JSON::success() : OC_JSON::error();
				} catch (\Exception $e) {
					OC_JSON::error(array('data' => array('message' => $e->getMessage())));
				}
			}
			break;
		case 'informRecipients':
			$l = \OC::$server->getL10N('core');
			$shareType = (int) $_POST['shareType'];
			$itemType = $_POST['itemType'];
			$itemSource = $_POST['itemSource'];
			$recipient = $_POST['recipient'];

			if($shareType === \OCP\Share::SHARE_TYPE_USER) {
				$recipientList[] = $recipient;
			} elseif ($shareType === \OCP\Share::SHARE_TYPE_GROUP) {
				$recipientList = \OC_Group::usersInGroup($recipient);
			}
			// don't send a mail to the user who shared the file
			$recipientList = array_diff($recipientList, array(\OCP\User::getUser()));

			$mailNotification = new OC\Share\MailNotifications();
			$result = $mailNotification->sendInternalShareMail($recipientList, $itemSource, $itemType);

			\OCP\Share::setSendMailStatus($itemType, $itemSource, $shareType, $recipient, true);

			if (empty($result)) {
				OCP\JSON::success();
			} else {
				OCP\JSON::error(array(
					'data' => array(
						'message' => $l->t("Couldn't send mail to following users: %s ",
								implode(', ', $result)
								)
						)
					));
			}
			break;
		case 'informRecipientsDisabled':
			$itemSource = $_POST['itemSource'];
			$shareType = $_POST['shareType'];
			$itemType = $_POST['itemType'];
			$recipient = $_POST['recipient'];
			\OCP\Share::setSendMailStatus($itemType, $itemSource, $shareType, $recipient, false);
			OCP\JSON::success();
			break;

		case 'email':
			// read post variables
			$link = $_POST['link'];
			$file = $_POST['file'];
			$to_address = $_POST['toaddress'];

			$mailNotification = new \OC\Share\MailNotifications();

			$expiration = null;
			if (isset($_POST['expiration']) && $_POST['expiration'] !== '') {
				try {
					$date = new DateTime($_POST['expiration']);
					$expiration = $date->getTimestamp();
				} catch (Exception $e) {
					\OCP\Util::writeLog('sharing', "Couldn't read date: " . $e->getMessage(), \OCP\Util::ERROR);
				}

			}

			$result = $mailNotification->sendLinkShareMail($to_address, $file, $link, $expiration);
			if(empty($result)) {
				\OCP\JSON::success();
			} else {
				$l = \OC::$server->getL10N('core');
				OCP\JSON::error(array(
					'data' => array(
						'message' => $l->t("Couldn't send mail to following users: %s ",
								implode(', ', $result)
							)
					)
				));
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
		case 'getShareWithEmail':
			$result = array();
			if (isset($_GET['search'])) {
				$cm = OC::$server->getContactsManager();
				if (!is_null($cm) && $cm->isEnabled()) {
					$contacts = $cm->search($_GET['search'], array('FN', 'EMAIL'));
					foreach ($contacts as $contact) {
						if (!isset($contact['EMAIL'])) {
							continue;
						}

						$emails = $contact['EMAIL'];
						if (!is_array($emails)) {
							$emails = array($emails);
						}

						foreach($emails as $email) {
							$result[] = array(
								'id' => $contact['id'],
								'email' => $email,
								'displayname' => $contact['FN'],
							);
						}
					}
				}
			}
			OC_JSON::success(array('data' => $result));
			break;
		case 'getShareWith':
			if (isset($_GET['search'])) {
				$shareWithinGroupOnly = OC\Share\Share::shareWithGroupMembersOnly();
				$shareWith = array();
				$groups = OC_Group::getGroups($_GET['search']);
				if ($shareWithinGroupOnly) {
					$usergroups = OC_Group::getUserGroups(OC_User::getUser());
					$groups = array_intersect($groups, $usergroups);
				}
				$count = 0;
				$users = array();
				$limit = 0;
				$offset = 0;
				while ($count < 15 && count($users) == $limit) {
					$limit = 15 - $count;
					if ($shareWithinGroupOnly) {
						$users = OC_Group::DisplayNamesInGroups($usergroups, $_GET['search'], $limit, $offset);
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
								'value' => array(
									'shareType' => OCP\Share::SHARE_TYPE_USER,
									'shareWith' => $uid)
							);
							$count++;
						}
					}
				}
				$count = 0;

				// enable l10n support
				$l = \OC::$server->getL10N('core');

				foreach ($groups as $group) {
					if ($count < 15) {
						if (!isset($_GET['itemShares'])
							|| !isset($_GET['itemShares'][OCP\Share::SHARE_TYPE_GROUP])
							|| !is_array($_GET['itemShares'][OCP\Share::SHARE_TYPE_GROUP])
							|| !in_array($group, $_GET['itemShares'][OCP\Share::SHARE_TYPE_GROUP])) {
							$shareWith[] = array(
								'label' => $group,
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

				// allow user to add unknown remote addresses for server-to-server share
				$backend = \OCP\Share::getBackend($_GET['itemType']);
				if ($backend->isShareTypeAllowed(\OCP\Share::SHARE_TYPE_REMOTE)) {
					if (substr_count($_GET['search'], '@') === 1) {
						$shareWith[] = array(
							'label' => $_GET['search'],
							'value' => array(
								'shareType' => \OCP\Share::SHARE_TYPE_REMOTE,
								'shareWith' => $_GET['search']
							)
						);
					}
				}

				$sorter = new \OC\Share\SearchResultSorter($_GET['search'],
														   'label',
														   new \OC\Log());
				usort($shareWith, array($sorter, 'sort'));
				OC_JSON::success(array('data' => $shareWith));
			}
			break;
	}
}

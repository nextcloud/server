<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Craig Morrissey <craig@owncloud.com>
 * @author dampfklon <me@dampfklon.de>
 * @author Felix Böhm <felixboehm@gmx.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Ramiro Aparicio <rapariciog@gmail.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
					$itemSourceName = isset($_POST['itemSourceName']) ? (string)$_POST['itemSourceName'] : null;

					/*
					 * Nasty nasty fix for https://github.com/owncloud/core/issues/19950
					 */
					$passwordChanged = null;
					if (is_array($shareWith)) {
						$passwordChanged = ($shareWith['passwordChanged'] === 'true');
						if ($shareType === OCP\Share::SHARE_TYPE_LINK && $shareWith['password'] === '') {
							$shareWith = null;
						} else {
							$shareWith = $shareWith['password'];
						}
					} else {
						/*
						 * We need this branch since the calendar and contacts also use this
						 * endpoint
						 */
						if ($shareType === OCP\Share::SHARE_TYPE_LINK && $shareWith === '') {
							$shareWith = null;
						}
					}

 					$itemSourceName=(isset($_POST['itemSourceName'])) ? (string)$_POST['itemSourceName']:'';

					$token = OCP\Share::shareItem(
						$_POST['itemType'],
						$_POST['itemSource'],
						$shareType,
						$shareWith,
						$_POST['permissions'],
						$itemSourceName,
						(!empty($_POST['expirationDate']) ? new \DateTime((string)$_POST['expirationDate']) : null),
						$passwordChanged
					);

					if (is_string($token)) {
						OC_JSON::success(array('data' => array('token' => $token)));
					} else {
						OC_JSON::success();
					}
				} catch (\OC\HintException $exception) {
					OC_JSON::error(array('data' => array('message' => $exception->getHint())));
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
					$shareWith = (string)$_POST['shareWith'];
				}
				$return = OCP\Share::unshare((string)$_POST['itemType'],(string) $_POST['itemSource'], (int)$_POST['shareType'], $shareWith);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
		case 'setPermissions':
			if (isset($_POST['shareType']) && isset($_POST['shareWith']) && isset($_POST['permissions'])) {
				$return = OCP\Share::setPermissions(
					(string)$_POST['itemType'],
					(string)$_POST['itemSource'],
					(int)$_POST['shareType'],
					(string)$_POST['shareWith'],
					(int)$_POST['permissions']
				);
				($return) ? OC_JSON::success() : OC_JSON::error();
			}
			break;
		case 'setExpirationDate':
			if (isset($_POST['date'])) {
				try {
					$return = OCP\Share::setExpirationDate((string)$_POST['itemType'], (string)$_POST['itemSource'], (string)$_POST['date']);
					($return) ? OC_JSON::success() : OC_JSON::error();
				} catch (\Exception $e) {
					OC_JSON::error(array('data' => array('message' => $e->getMessage())));
				}
			}
			break;
		case 'informRecipients':
			$l = \OC::$server->getL10N('core');
			$shareType = (int) $_POST['shareType'];
			$itemType = (string)$_POST['itemType'];
			$itemSource = (string)$_POST['itemSource'];
			$recipient = (string)$_POST['recipient'];

			if($shareType === \OCP\Share::SHARE_TYPE_USER) {
				$recipientList[] = $recipient;
			} elseif ($shareType === \OCP\Share::SHARE_TYPE_GROUP) {
				$recipientList = \OC_Group::usersInGroup($recipient);
			}
			// don't send a mail to the user who shared the file
			$recipientList = array_diff($recipientList, array(\OCP\User::getUser()));

			$mailNotification = new \OC\Share\MailNotifications(
				\OC::$server->getUserSession()->getUser()->getUID(),
				\OC::$server->getConfig(),
				\OC::$server->getL10N('lib'),
				\OC::$server->getMailer(),
				\OC::$server->getLogger(),
				$defaults
			);
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
			$itemSource = (string)$_POST['itemSource'];
			$shareType = (int)$_POST['shareType'];
			$itemType = (string)$_POST['itemType'];
			$recipient = (string)$_POST['recipient'];
			\OCP\Share::setSendMailStatus($itemType, $itemSource, $shareType, $recipient, false);
			OCP\JSON::success();
			break;

		case 'email':
			// read post variables
			$link = (string)$_POST['link'];
			$file = (string)$_POST['file'];
			$to_address = (string)$_POST['toaddress'];

			$mailNotification = new \OC\Share\MailNotifications(
				\OC::$server->getUserSession()->getUser()->getUID(),
				\OC::$server->getConfig(),
				\OC::$server->getL10N('lib'),
				\OC::$server->getMailer(),
				\OC::$server->getLogger(),
				$defaults
			);

			$expiration = null;
			if (isset($_POST['expiration']) && $_POST['expiration'] !== '') {
				try {
					$date = new DateTime((string)$_POST['expiration']);
					$expiration = $date->getTimestamp();
				} catch (Exception $e) {
					\OCP\Util::writeLog('sharing', "Couldn't read date: " . $e->getMessage(), \OCP\Util::ERROR);
				}

			}

			$result = $mailNotification->sendLinkShareMail($to_address, $file, $link, $expiration);
			if(empty($result)) {
				// Get the token from the link
				$linkParts = explode('/', $link);
				$token = array_pop($linkParts);

				// Get the share for the token
				$share = \OCP\Share::getShareByToken($token, false);
				if ($share !== false) {
					$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
					$file = '/' . ltrim($file, '/');

					// Check whether share belongs to the user and whether the file is the same
					if ($share['file_target'] === $file && $share['uid_owner'] === $currentUser) {

						// Get the path for the user
						$view = new \OC\Files\View('/' . $currentUser . '/files');
						$fileId = (int) $share['item_source'];
						$path = $view->getPath((int) $share['item_source']);

						if ($path !== null) {
							$event = \OC::$server->getActivityManager()->generateEvent();
							$event->setApp(\OCA\Files_Sharing\Activity::FILES_SHARING_APP)
								->setType(\OCA\Files_Sharing\Activity::TYPE_SHARED)
								->setAuthor($currentUser)
								->setAffectedUser($currentUser)
								->setObject('files', $fileId, $path)
								->setSubject(\OCA\Files_Sharing\Activity::SUBJECT_SHARED_EMAIL, [$path, $to_address]);
							\OC::$server->getActivityManager()->publish($event);
						}
					}
				}

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
				$return = OCP\Share::getItemsShared((string)$_GET['itemType'], OCP\Share::FORMAT_STATUSES);
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
						(string)$_GET['itemType'],
						(string)$_GET['itemSource'],
						OCP\Share::FORMAT_NONE,
						null,
						true
					);
				} else {
					$reshare = false;
				}
				if ($_GET['checkShares'] == 'true') {
					$sharesTMP = OCP\Share::getItemShared(
						(string)$_GET['itemType'],
						(string)$_GET['itemSource'],
						OCP\Share::FORMAT_NONE,
						null,
						true
					);
					$ids = [];
					$shares = [];
					foreach($sharesTMP as $share) {
						if (!isset($ids[$share['id']])) {
							$ids[$share['id']] = true;
							$shares[] = $share;
						}
					}
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
					$contacts = $cm->search((string)$_GET['search'], array('FN', 'EMAIL'));
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
				$groups = OC_Group::getGroups((string)$_GET['search']);
				if ($shareWithinGroupOnly) {
					$usergroups = OC_Group::getUserGroups(OC_User::getUser());
					$groups = array_intersect($groups, $usergroups);
				}

				$sharedUsers = [];
				$sharedGroups = [];
				if (isset($_GET['itemShares'])) {
					if (isset($_GET['itemShares'][OCP\Share::SHARE_TYPE_USER]) &&
					    is_array($_GET['itemShares'][OCP\Share::SHARE_TYPE_USER])) {
						$sharedUsers = $_GET['itemShares'][OCP\Share::SHARE_TYPE_USER];
					}

					if (isset($_GET['itemShares'][OCP\Share::SHARE_TYPE_GROUP]) &&
					    is_array($_GET['itemShares'][OCP\Share::SHARE_TYPE_GROUP])) {
						$sharedGroups = $_GET['itemShares'][OCP\Share::SHARE_TYPE_GROUP];
					}
				}

				$count = 0;
				$users = array();
				$limit = 0;
				$offset = 0;
				// limit defaults to 15 if not specified via request parameter and can be no larger than 500
				$request_limit = min((int)$_GET['limit'] ?: 15, 500);
				while ($count < $request_limit && count($users) == $limit) {
					$limit = $request_limit - $count;
					if ($shareWithinGroupOnly) {
						$users = OC_Group::displayNamesInGroups($usergroups, (string)$_GET['search'], $limit, $offset);
					} else {
						$users = OC_User::getDisplayNames((string)$_GET['search'], $limit, $offset);
					}

					$offset += $limit;
					foreach ($users as $uid => $displayName) {
						if (in_array($uid, $sharedUsers)) {
							continue;
						}

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
					if (in_array($group, $sharedGroups)) {
						continue;
					}

					if ($count < $request_limit) {
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
				$backend = \OCP\Share::getBackend((string)$_GET['itemType']);
				if ($backend->isShareTypeAllowed(\OCP\Share::SHARE_TYPE_REMOTE)) {
					if (substr_count((string)$_GET['search'], '@') >= 1) {
						$shareWith[] = array(
							'label' => (string)$_GET['search'],
							'value' => array(
								'shareType' => \OCP\Share::SHARE_TYPE_REMOTE,
								'shareWith' => (string)$_GET['search']
							)
						);
					}
					$contactManager = \OC::$server->getContactsManager();
					$addressBookContacts = $contactManager->search($_GET['search'], ['CLOUD', 'FN']);
					foreach ($addressBookContacts as $contact) {
						if (isset($contact['CLOUD'])) {
							foreach ($contact['CLOUD'] as $cloudId) {
								$shareWith[] = array(
									'label' => $contact['FN'] . ' (' . $cloudId . ')',
									'value' => array(
										'shareType' => \OCP\Share::SHARE_TYPE_REMOTE,
										'shareWith' => $cloudId
									)
								);
							}
						}
					}
				}

				$sharingAutocompletion = \OC::$server->getConfig()
					->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes');

				if ($sharingAutocompletion !== 'yes') {
					$searchTerm = strtolower($_GET['search']);
					$shareWith = array_filter($shareWith, function($user) use ($searchTerm) {
						return strtolower($user['label']) === $searchTerm
							|| strtolower($user['value']['shareWith']) === $searchTerm;
					});
				}

				$sorter = new \OC\Share\SearchResultSorter((string)$_GET['search'],
														   'label',
														   \OC::$server->getLogger());
				usort($shareWith, array($sorter, 'sort'));
				OC_JSON::success(array('data' => $shareWith));
			}
			break;
	}
}

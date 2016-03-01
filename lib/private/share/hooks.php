<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Share;

class Hooks extends \OC\Share\Constants {

	/**
	 * remember which targets need to be updated in the post addToGroup Hook
	 * @var array
	 */
	private static $updateTargets = array();

	/**
	 * Function that is called after a user is deleted. Cleans up the shares of that user.
	 * @param array $arguments
	 */
	public static function post_deleteUser($arguments) {
		// Delete any items shared with the deleted user
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share`'
			.' WHERE `share_with` = ? AND (`share_type` = ? OR `share_type` = ?)');
		$query->execute(array($arguments['uid'], self::SHARE_TYPE_USER, self::$shareTypeGroupUserUnique));
		// Delete any items the deleted user shared
		$query = \OC_DB::prepare('SELECT `id` FROM `*PREFIX*share` WHERE `uid_owner` = ?');
		$result = $query->execute(array($arguments['uid']));
		while ($item = $result->fetchRow()) {
			Helper::delete($item['id']);
		}
	}


	/**
	 * Function that is called before a user is added to a group.
	 * check if we need to create a unique target for the user
	 * @param array $arguments
	 */
	public static function pre_addToGroup($arguments) {
		$currentUser = \OC::$server->getUserSession()->getUser();
		$currentUserID = is_null($currentUser) ? '' : $currentUser->getUID();

		// setup filesystem for added user if it isn't the current user
		if($currentUserID !== $arguments['uid']) {
			\OC_Util::tearDownFS();
			\OC_Util::setupFS($arguments['uid']);
		}

		/** @var \OC\DB\Connection $db */
		$db = \OC::$server->getDatabaseConnection();

		$insert = $db->createQueryBuilder();

		$select = $db->createQueryBuilder();
		// Find the group shares and check if the user needs a unique target
		$select->select('*')
			->from('`*PREFIX*share`')
			->where($select->expr()->andX(
				$select->expr()->eq('`share_type`', ':shareType'),
				$select->expr()->eq('`share_with`', ':shareWith')
			))
			->setParameter('shareType', self::SHARE_TYPE_GROUP)
			->setParameter('shareWith', $arguments['gid']);

		$result = $select->execute();

		while ($item = $result->fetch()) {

			$itemTarget = Helper::generateTarget(
				$item['item_type'],
				$item['item_source'],
				self::SHARE_TYPE_USER,
				$arguments['uid'],
				$item['uid_owner'],
				null,
				$item['parent']
			);

			if ($item['item_type'] === 'file' || $item['item_type'] === 'folder') {
				$fileTarget = Helper::generateTarget(
					$item['item_type'],
					$item['file_target'],
					self::SHARE_TYPE_USER,
					$arguments['uid'],
					$item['uid_owner'],
					null,
					$item['parent']
				);
			} else {
				$fileTarget = null;
			}


			// Insert an extra row for the group share if the item or file target is unique for this user
			if (
				($fileTarget === null && $itemTarget != $item['item_target'])
				|| ($fileTarget !== null && $fileTarget !== $item['file_target'])
			) {
				self::$updateTargets[$arguments['gid']][] = [
					'`item_type`' => $insert->expr()->literal($item['item_type']),
					'`item_source`' => $insert->expr()->literal($item['item_source']),
					'`item_target`' => $insert->expr()->literal($itemTarget),
					'`file_target`' => $insert->expr()->literal($fileTarget),
					'`parent`' => $insert->expr()->literal($item['id']),
					'`share_type`' => $insert->expr()->literal(self::$shareTypeGroupUserUnique),
					'`share_with`' => $insert->expr()->literal($arguments['uid']),
					'`uid_owner`' => $insert->expr()->literal($item['uid_owner']),
					'`permissions`' => $insert->expr()->literal($item['permissions']),
					'`stime`' => $insert->expr()->literal($item['stime']),
					'`file_source`' => $insert->expr()->literal($item['file_source']),
				];
			}
		}

		// re-setup old filesystem state
		if($currentUserID !== $arguments['uid']) {
			\OC_Util::tearDownFS();
			if($currentUserID !== '') {
				\OC_Util::setupFS($currentUserID);
			}
		}
	}

	/**
	 * Function that is called after a user is added to a group.
	 * add unique target for the user if needed
	 * @param array $arguments
	 */
	public static function post_addToGroup($arguments) {
		/** @var \OC\DB\Connection $db */
		$db = \OC::$server->getDatabaseConnection();

		$insert = $db->createQueryBuilder();
		$insert->insert('`*PREFIX*share`');

		if (isset(self::$updateTargets[$arguments['gid']])) {
			foreach (self::$updateTargets[$arguments['gid']] as $newTarget) {
				$insert->values($newTarget);
				$insert->execute();
			}
			unset(self::$updateTargets[$arguments['gid']]);
		}
	}

	/**
	 * Function that is called after a user is removed from a group. Shares are cleaned up.
	 * @param array $arguments
	 */
	public static function post_removeFromGroup($arguments) {
		$sql = 'SELECT `id`, `share_type` FROM `*PREFIX*share`'
			.' WHERE (`share_type` = ? AND `share_with` = ?) OR (`share_type` = ? AND `share_with` = ?)';
		$result = \OC_DB::executeAudited($sql, array(self::SHARE_TYPE_GROUP, $arguments['gid'],
			self::$shareTypeGroupUserUnique, $arguments['uid']));
		while ($item = $result->fetchRow()) {
			if ($item['share_type'] == self::SHARE_TYPE_GROUP) {
				// Delete all reshares by this user of the group share
				Helper::delete($item['id'], true, $arguments['uid']);
			} else {
				Helper::delete($item['id']);
			}
		}
	}

	/**
	 * Function that is called after a group is removed. Cleans up the shares to that group.
	 * @param array $arguments
	 */
	public static function post_deleteGroup($arguments) {
		$sql = 'SELECT `id` FROM `*PREFIX*share` WHERE `share_type` = ? AND `share_with` = ?';
		$result = \OC_DB::executeAudited($sql, array(self::SHARE_TYPE_GROUP, $arguments['gid']));
		while ($item = $result->fetchRow()) {
			Helper::delete($item['id']);
		}
	}

}

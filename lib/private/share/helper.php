<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
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

namespace OC\Share;

class Helper extends \OC\Share\Constants {

	/**
	 * Generate a unique target for the item
	 * @param string $itemType
	 * @param string $itemSource
	 * @param int $shareType SHARE_TYPE_USER, SHARE_TYPE_GROUP, or SHARE_TYPE_LINK
	 * @param string $shareWith User or group the item is being shared with
	 * @param string $uidOwner User that is the owner of shared item
	 * @param string $suggestedTarget The suggested target originating from a reshare (optional)
	 * @param int $groupParent The id of the parent group share (optional)
	 * @throws \Exception
	 * @return string Item target
	 */
	public static function generateTarget($itemType, $itemSource, $shareType, $shareWith, $uidOwner, $suggestedTarget = null, $groupParent = null) {
		// FIXME: $uidOwner and $groupParent seems to be unused
		$backend = \OC\Share\Share::getBackend($itemType);
		if ($shareType === self::SHARE_TYPE_LINK || $shareType === self::SHARE_TYPE_REMOTE) {
			if (isset($suggestedTarget)) {
				return $suggestedTarget;
			}
			return $backend->generateTarget($itemSource, false);
		} else {
			if ($itemType == 'file' || $itemType == 'folder') {
				$column = 'file_target';
				$columnSource = 'file_source';
			} else {
				$column = 'item_target';
				$columnSource = 'item_source';
			}
			if ($shareType == self::SHARE_TYPE_USER) {
				// Share with is a user, so set share type to user and groups
				$shareType = self::$shareTypeUserAndGroups;
			}
			$exclude = array();

			$result = \OCP\Share::getItemsSharedWithUser($itemType, $shareWith);
			foreach ($result as $row) {
				if ($row['permissions'] > 0) {
					$exclude[] = $row[$column];
				}
			}

			// Check if suggested target exists first
			if (!isset($suggestedTarget)) {
				$suggestedTarget = $itemSource;
			}
			if ($shareType == self::SHARE_TYPE_GROUP) {
				$target = $backend->generateTarget($suggestedTarget, false, $exclude);
			} else {
				$target = $backend->generateTarget($suggestedTarget, $shareWith, $exclude);
			}

			return $target;
		}
	}

	/**
	 * Delete all reshares and group share children of an item
	 * @param int $parent Id of item to delete
	 * @param bool $excludeParent If true, exclude the parent from the delete (optional)
	 * @param string $uidOwner The user that the parent was shared with (optional)
	 * @param int $newParent new parent for the childrens
	 * @param bool $excludeGroupChildren exclude group children elements
	 */
	public static function delete($parent, $excludeParent = false, $uidOwner = null, $newParent = null, $excludeGroupChildren = false) {
		$ids = array($parent);
		$deletedItems = array();
		$changeParent = array();
		$parents = array($parent);
		while (!empty($parents)) {
			$parents = "'".implode("','", $parents)."'";
			// Check the owner on the first search of reshares, useful for
			// finding and deleting the reshares by a single user of a group share
			$params = array();
			if (count($ids) == 1 && isset($uidOwner)) {
				// FIXME: don't concat $parents, use Docrine's PARAM_INT_ARRAY approach
				$queryString = 'SELECT `id`, `share_with`, `item_type`, `share_type`, ' .
					'`item_target`, `file_target`, `parent` ' .
					'FROM `*PREFIX*share` ' .
					'WHERE `parent` IN ('.$parents.') AND `uid_owner` = ? ';
				$params[] = $uidOwner;
			} else {
				$queryString = 'SELECT `id`, `share_with`, `item_type`, `share_type`, ' .
					'`item_target`, `file_target`, `parent`, `uid_owner` ' .
					'FROM `*PREFIX*share` WHERE `parent` IN ('.$parents.') ';
			}
			if ($excludeGroupChildren) {
				$queryString .= ' AND `share_type` != ?';
				$params[] = self::$shareTypeGroupUserUnique;
			}
			$query = \OC_DB::prepare($queryString);
			$result = $query->execute($params);
			// Reset parents array, only go through loop again if items are found
			$parents = array();
			while ($item = $result->fetchRow()) {
				$tmpItem = array(
					'id' => $item['id'],
					'shareWith' => $item['share_with'],
					'itemTarget' => $item['item_target'],
					'itemType' => $item['item_type'],
					'shareType' => (int)$item['share_type'],
				);
				if (isset($item['file_target'])) {
					$tmpItem['fileTarget'] = $item['file_target'];
				}
				// if we have a new parent for the child we remember the child
				// to update the parent, if not we add it to the list of items
				// which should be deleted
				if ($newParent !== null) {
					$changeParent[] = $item['id'];
				} else {
					$deletedItems[] = $tmpItem;
					$ids[] = $item['id'];
					$parents[] = $item['id'];
				}
			}
		}
		if ($excludeParent) {
			unset($ids[0]);
		}

		if (!empty($changeParent)) {
			$idList = "'".implode("','", $changeParent)."'";
			$query = \OC_DB::prepare('UPDATE `*PREFIX*share` SET `parent` = ? WHERE `id` IN ('.$idList.')');
			$query->execute(array($newParent));
		}

		if (!empty($ids)) {
			$idList = "'".implode("','", $ids)."'";
			$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share` WHERE `id` IN ('.$idList.')');
			$query->execute();
		}

		return $deletedItems;
	}

	/**
	 * get default expire settings defined by the admin
	 * @return array contains 'defaultExpireDateSet', 'enforceExpireDate', 'expireAfterDays'
	 */
	public static function getDefaultExpireSetting() {

		$defaultExpireSettings = array('defaultExpireDateSet' => false);

		// get default expire settings
		$defaultExpireDate = \OC_Appconfig::getValue('core', 'shareapi_default_expire_date', 'no');
		if ($defaultExpireDate === 'yes') {
			$enforceExpireDate = \OC_Appconfig::getValue('core', 'shareapi_enforce_expire_date', 'no');
			$defaultExpireSettings['defaultExpireDateSet'] = true;
			$defaultExpireSettings['expireAfterDays'] = (int)\OC_Appconfig::getValue('core', 'shareapi_expire_after_n_days', '7');
			$defaultExpireSettings['enforceExpireDate'] = $enforceExpireDate === 'yes' ? true : false;
		}

		return $defaultExpireSettings;
	}

	public static function calcExpireDate() {
		$expireAfter = \OC\Share\Share::getExpireInterval() * 24 * 60 * 60;
		$expireAt = time() + $expireAfter;
		$date = new \DateTime();
		$date->setTimestamp($expireAt);
		$date->setTime(0, 0, 0);
		//$dateString = $date->format('Y-m-d') . ' 00:00:00';

		return $date;

	}

	/**
	 * calculate expire date
	 * @param array $defaultExpireSettings contains 'defaultExpireDateSet', 'enforceExpireDate', 'expireAfterDays'
	 * @param int $creationTime timestamp when the share was created
	 * @param int $userExpireDate expire timestamp set by the user
	 * @return mixed integer timestamp or False
	 */
	public static function calculateExpireDate($defaultExpireSettings, $creationTime, $userExpireDate = null) {

		$expires = false;
		$defaultExpires = null;

		if (!empty($defaultExpireSettings['defaultExpireDateSet'])) {
			$defaultExpires = $creationTime + $defaultExpireSettings['expireAfterDays'] * 86400;
		}


		if (isset($userExpireDate)) {
			// if the admin decided to enforce the default expire date then we only take
			// the user defined expire date of it is before the default expire date
			if ($defaultExpires && !empty($defaultExpireSettings['enforceExpireDate'])) {
				$expires = min($userExpireDate, $defaultExpires);
			} else {
				$expires = $userExpireDate;
			}
		} else if ($defaultExpires && !empty($defaultExpireSettings['enforceExpireDate'])) {
			$expires = $defaultExpires;
		}

		return $expires;
	}
}

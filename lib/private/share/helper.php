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
	public static function generateTarget($itemType, $itemSource, $shareType, $shareWith, $uidOwner,
		$suggestedTarget = null, $groupParent = null) {
		$backend = \OC\Share\Share::getBackend($itemType);
		if ($shareType == self::SHARE_TYPE_LINK) {
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
				$userAndGroups = array_merge(array($shareWith), \OC_Group::getUserGroups($shareWith));
			} else {
				$userAndGroups = false;
			}
			$exclude = null;
			// Backend has 3 opportunities to generate a unique target
			for ($i = 0; $i < 2; $i++) {
				// Check if suggested target exists first
				if ($i == 0 && isset($suggestedTarget)) {
					$target = $suggestedTarget;
				} else {
					if ($shareType == self::SHARE_TYPE_GROUP) {
						$target = $backend->generateTarget($itemSource, false, $exclude);
					} else {
						$target = $backend->generateTarget($itemSource, $shareWith, $exclude);
					}
					if (is_array($exclude) && in_array($target, $exclude)) {
						break;
					}
				}
				// Check if target already exists
				$checkTarget = \OC\Share\Share::getItems($itemType, $target, $shareType, $shareWith);
				if (!empty($checkTarget)) {
					foreach ($checkTarget as $item) {
						// Skip item if it is the group parent row
						if (isset($groupParent) && $item['id'] == $groupParent) {
							if (count($checkTarget) == 1) {
								return $target;
							} else {
								continue;
							}
						}
						if ($item['uid_owner'] == $uidOwner) {
							if ($itemType == 'file' || $itemType == 'folder') {
								$meta = \OC\Files\Filesystem::getFileInfo($itemSource);
								if ($item['file_source'] == $meta['fileid']) {
									return $target;
								}
							} else if ($item['item_source'] == $itemSource) {
								return $target;
							}
						}
					}
					if (!isset($exclude)) {
						$exclude = array();
					}
					// Find similar targets to improve backend's chances to generate a unqiue target
					if ($userAndGroups) {
						if ($column == 'file_target') {
							$checkTargets = \OC_DB::prepare('SELECT `'.$column.'` FROM `*PREFIX*share`'
								.' WHERE `item_type` IN (\'file\', \'folder\')'
								.' AND `share_type` IN (?,?,?)'
								.' AND `share_with` IN (\''.implode('\',\'', $userAndGroups).'\')');
							$result = $checkTargets->execute(array(self::SHARE_TYPE_USER, self::SHARE_TYPE_GROUP,
								self::$shareTypeGroupUserUnique));
						} else {
							$checkTargets = \OC_DB::prepare('SELECT `'.$column.'` FROM `*PREFIX*share`'
								.' WHERE `item_type` = ? AND `share_type` IN (?,?,?)'
								.' AND `share_with` IN (\''.implode('\',\'', $userAndGroups).'\')');
							$result = $checkTargets->execute(array($itemType, self::SHARE_TYPE_USER,
								self::SHARE_TYPE_GROUP, self::$shareTypeGroupUserUnique));
						}
					} else {
						if ($column == 'file_target') {
							$checkTargets = \OC_DB::prepare('SELECT `'.$column.'` FROM `*PREFIX*share`'
								.' WHERE `item_type` IN (\'file\', \'folder\')'
								.' AND `share_type` = ? AND `share_with` = ?');
							$result = $checkTargets->execute(array(self::SHARE_TYPE_GROUP, $shareWith));
						} else {
							$checkTargets = \OC_DB::prepare('SELECT `'.$column.'` FROM `*PREFIX*share`'
								.' WHERE `item_type` = ? AND `share_type` = ? AND `share_with` = ?');
							$result = $checkTargets->execute(array($itemType, self::SHARE_TYPE_GROUP, $shareWith));
						}
					}
					while ($row = $result->fetchRow()) {
						$exclude[] = $row[$column];
					}
				} else {
					return $target;
				}
			}
		}
		$message = 'Sharing backend registered for '.$itemType.' did not generate a unique target for '.$itemSource;
		\OC_Log::write('OCP\Share', $message, \OC_Log::ERROR);
		throw new \Exception($message);
	}

	/**
	 * Delete all reshares of an item
	 * @param int $parent Id of item to delete
	 * @param bool $excludeParent If true, exclude the parent from the delete (optional)
	 * @param string $uidOwner The user that the parent was shared with (optional)
	 */
	public static function delete($parent, $excludeParent = false, $uidOwner = null) {
		$ids = array($parent);
		$parents = array($parent);
		while (!empty($parents)) {
			$parents = "'".implode("','", $parents)."'";
			// Check the owner on the first search of reshares, useful for
			// finding and deleting the reshares by a single user of a group share
			if (count($ids) == 1 && isset($uidOwner)) {
				$query = \OC_DB::prepare('SELECT `id`, `uid_owner`, `item_type`, `item_target`, `parent`'
					.' FROM `*PREFIX*share` WHERE `parent` IN ('.$parents.') AND `uid_owner` = ?');
				$result = $query->execute(array($uidOwner));
			} else {
				$query = \OC_DB::prepare('SELECT `id`, `item_type`, `item_target`, `parent`, `uid_owner`'
					.' FROM `*PREFIX*share` WHERE `parent` IN ('.$parents.')');
				$result = $query->execute();
			}
			// Reset parents array, only go through loop again if items are found
			$parents = array();
			while ($item = $result->fetchRow()) {
				// Search for a duplicate parent share, this occurs when an
				// item is shared to the same user through a group and user or the
				// same item is shared by different users
				$userAndGroups = array_merge(array($item['uid_owner']), \OC_Group::getUserGroups($item['uid_owner']));
				$query = \OC_DB::prepare('SELECT `id`, `permissions` FROM `*PREFIX*share`'
					.' WHERE `item_type` = ?'
					.' AND `item_target` = ?'
					.' AND `share_type` IN (?,?,?)'
					.' AND `share_with` IN (\''.implode('\',\'', $userAndGroups).'\')'
					.' AND `uid_owner` != ? AND `id` != ?');
				$duplicateParent = $query->execute(array($item['item_type'], $item['item_target'],
					self::SHARE_TYPE_USER, self::SHARE_TYPE_GROUP, self::$shareTypeGroupUserUnique,
					$item['uid_owner'], $item['parent']))->fetchRow();
				if ($duplicateParent) {
					// Change the parent to the other item id if share permission is granted
					if ($duplicateParent['permissions'] & \OCP\PERMISSION_SHARE) {
						$query = \OC_DB::prepare('UPDATE `*PREFIX*share` SET `parent` = ? WHERE `id` = ?');
						$query->execute(array($duplicateParent['id'], $item['id']));
						continue;
					}
				}
				$ids[] = $item['id'];
				$parents[] = $item['id'];
			}
		}
		if ($excludeParent) {
			unset($ids[0]);
		}
		if (!empty($ids)) {
			$ids = "'".implode("','", $ids)."'";
			$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share` WHERE `id` IN ('.$ids.')');
			$query->execute();
		}
	}

	/**
	 * @brief get default expire settings defined by the admin
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

	/**
	 * @brief calculate expire date
	 * @param array $defaultExpireSettings contains 'defaultExpireDateSet', 'enforceExpireDate', 'expireAfterDays'
	 * @param int $creationTime timestamp when the share was created
	 * @param int $userExpireDate expire timestamp set by the user
	 * @return mixed integer timestamp or False
	 */
	public static function calculateExpireDate($defaultExpireSettings, $creationTime, $userExpireDate = null) {

		$expires = false;

		if (!empty($defaultExpireSettings['defaultExpireDateSet'])) {
			$expires = $creationTime + $defaultExpireSettings['expireAfterDays'] * 86400;
		}


		if (isset($userExpireDate)) {
			// if the admin decided to enforce the default expire date then we only take
			// the user defined expire date of it is before the default expire date
			if ($expires && !empty($defaultExpireSettings['enforceExpireDate'])) {
				$expires = min($userExpireDate, $expires);
			} else {
				$expires = $userExpireDate;
			}
		}

		return $expires;
	}
}

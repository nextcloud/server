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
}

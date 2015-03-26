<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OC\Settings\Factory;

/**
 * @package OC\Settings\Factory
 */
class SubAdminFactory {
	/**
	 * Get the groups $uid is SubAdmin of
	 * @param string $uid
	 * @return array Array of groups that $uid is subadmin of
	 */
	function getSubAdminsOfGroups($uid) {
		return \OC_SubAdmin::getSubAdminsGroups($uid);
	}

	/**
	 * Whether the $group is accessible to $uid as subadmin
	 * @param string $uid
	 * @param string $group
	 * @return bool
	 */
	function isGroupAccessible($uid, $group) {
		return \OC_SubAdmin::isGroupAccessible($uid, $group);
	}

	/**
	 * Whether $uid is accessible to $subAdmin
	 * @param string $subAdmin
	 * @param string $uid
	 * @return bool
	 */
	function isUserAccessible($subAdmin, $uid) {
		return \OC_SubAdmin::isUserAccessible($subAdmin, $uid);
	}
}

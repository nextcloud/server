<?php
/**
 * @author Lukas Reschke
 * @copyright 2015 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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

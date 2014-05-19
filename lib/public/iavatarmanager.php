<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

/**
 * This class provides avatar functionality
 */

interface IAvatarManager {

	/**
	 * return a user specific instance of \OCP\IAvatar
	 * @see \OCP\IAvatar
	 * @param string $user the ownCloud user id
	 * @return \OCP\IAvatar
	 */
	function getAvatar($user);
}

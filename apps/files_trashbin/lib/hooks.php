<?php
/**
 * ownCloud - trash bin
 *
 * @author Bjoern Schiessle
 * @copyright 2013 Bjoern Schiessle schiessle@owncloud.com
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
 *
 */

/**
 * This class contains all hooks.
 */

namespace OCA\Files_Trashbin;

class Hooks {

	/**
	 * clean up user specific settings if user gets deleted
	 * @param array $params array with uid
	 *
	 * This function is connected to the pre_deleteUser signal of OC_Users
	 * to remove the used space for the trash bin stored in the database
	 */
	public static function deleteUser_hook($params) {
		if( \OCP\App::isEnabled('files_trashbin') ) {
			$uid = $params['uid'];
			Trashbin::deleteUser($uid);
			}
	}

	public static function post_write_hook($params) {
		Trashbin::resizeTrash(\OCP\User::getUser());
	}
}

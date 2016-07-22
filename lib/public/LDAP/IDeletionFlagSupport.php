<?php
/**
 * @author Roger Szabo <roger.szabo@web.de>
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

namespace OCP\LDAP;

/**
 * Interface IDeletionFlagSupport
 *
 * @package OCP\LDAP
 * @since 9.2.0
 */
interface IDeletionFlagSupport {
	/**
	 * Flag record for deletion.
	 * @param string $uid ownCloud user id
	 * @since 9.2.0
	 */
	public function flagRecord($uid);
	
	/**
	 * Unflag record for deletion.
	 * @param string $uid ownCloud user id
	 * @since 9.2.0
	 */
	public function unflagRecord($uid);
}

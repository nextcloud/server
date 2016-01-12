<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OC\Lock;

use OCP\Lock\ILockingProvider;

/**
 * Locking provider that does nothing.
 *
 * To be used when locking is disabled.
 */
class NoopLockingProvider implements ILockingProvider {

    /**
     * {@inheritdoc}
     */
	public function isLocked($path, $type) {
		return false;
	}

    /**
     * {@inheritdoc}
     */
	public function acquireLock($path, $type) {
		// do nothing
	}

	/**
     * {@inheritdoc}
	 */
	public function releaseLock($path, $type) {
		// do nothing
	}

	/**1
	 * {@inheritdoc}
	 */
	public function releaseAll() {
		// do nothing
	}

	/**
	 * {@inheritdoc}
	 */
	public function changeLock($path, $targetType) {
		// do nothing
	}
}

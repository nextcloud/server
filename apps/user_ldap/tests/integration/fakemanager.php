<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
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

namespace OCA\user_ldap\tests\integration;

/**
 * Class FakeManager
 *
 * this is a mock of \OCA\user_ldap\lib\user\Manager which is a dependency of
 * Access, that pulls plenty more things in. Because it is not needed in the
 * scope of these tests, we replace it with a mock.
 */
class FakeManager extends \OCA\user_ldap\lib\user\Manager {
	public function __construct() {
		$this->ocConfig = \OC::$server->getConfig();
		$this->image = new \OCP\Image();
	}
}

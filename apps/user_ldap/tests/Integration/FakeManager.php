<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 *
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

namespace OCA\User_LDAP\Tests\Integration;

/**
 * Class FakeManager
 *
 * this is a mock of \OCA\User_LDAP\User\Manager which is a dependency of
 * Access, that pulls plenty more things in. Because it is not needed in the
 * scope of these tests, we replace it with a mock.
 */
class FakeManager extends \OCA\User_LDAP\User\Manager {
	public function __construct() {
		$this->ocConfig = \OC::$server->getConfig();
		$this->image = new \OCP\Image();
	}
}

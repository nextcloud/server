<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

namespace Test\Security\CSRF;

class CsrfTokenTest extends \Test\TestCase {
	public function testGetEncryptedValue() {
		$csrfToken = new \OC\Security\CSRF\CsrfToken('MyCsrfToken');
		$this->assertSame(33, strlen($csrfToken->getEncryptedValue()));
		$this->assertSame(':', $csrfToken->getEncryptedValue()[16]);
	}

	public function testGetDecryptedValue() {
		$csrfToken = new \OC\Security\CSRF\CsrfToken('XlQhHjgWCgBXAEI0Khl+IQEiCXN2LUcDHAQTQAc1HQs=:qgkUlg8l3m8WnkOG4XM9Az33pAt1vSVMx4hcJFsxdqc=');
		$this->assertSame('/3JKTq2ldmzcDr1f5zDJ7Wt0lEgqqfKF', $csrfToken->getDecryptedValue());
	}
}

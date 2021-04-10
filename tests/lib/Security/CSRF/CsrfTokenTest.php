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

	public function testGetEncryptedValueStaysSameOnSecondRequest() {
		$csrfToken = new \OC\Security\CSRF\CsrfToken('MyCsrfToken');
		$tokenValue = $csrfToken->getEncryptedValue();
		$this->assertSame($tokenValue, $csrfToken->getEncryptedValue());
		$this->assertSame($tokenValue, $csrfToken->getEncryptedValue());
	}

	public function testGetDecryptedValue() {
		$a = 'abc';
		$b = 'def';
		$xorB64 = 'BQcF';
		$tokenVal = sprintf('%s:%s', $xorB64, base64_encode($a));
		$csrfToken = new \OC\Security\CSRF\CsrfToken($tokenVal);
		$this->assertSame($b, $csrfToken->getDecryptedValue());
	}
}

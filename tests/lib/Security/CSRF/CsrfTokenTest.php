<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Security\CSRF;

use OC\Security\CSRF\CsrfToken;

class CsrfTokenTest extends \Test\TestCase {
	public function testGetEncryptedValue(): void {
		$csrfToken = new CsrfToken('MyCsrfToken');
		$this->assertSame(33, strlen($csrfToken->getEncryptedValue()));
		$this->assertSame(':', $csrfToken->getEncryptedValue()[16]);
	}

	public function testGetEncryptedValueStaysSameOnSecondRequest(): void {
		$csrfToken = new CsrfToken('MyCsrfToken');
		$tokenValue = $csrfToken->getEncryptedValue();
		$this->assertSame($tokenValue, $csrfToken->getEncryptedValue());
		$this->assertSame($tokenValue, $csrfToken->getEncryptedValue());
	}

	public function testGetDecryptedValue(): void {
		$a = 'abc';
		$b = 'def';
		$xorB64 = 'BQcF';
		$tokenVal = sprintf('%s:%s', $xorB64, base64_encode($a));
		$csrfToken = new CsrfToken($tokenVal);
		$this->assertSame($b, $csrfToken->getDecryptedValue());
	}
}

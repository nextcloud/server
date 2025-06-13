<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Security\CSRF;

use OC\Security\CSRF\CsrfTokenGenerator;

class CsrfTokenGeneratorTest extends \Test\TestCase {
	/** @var \OCP\Security\ISecureRandom */
	private $random;
	/** @var \OC\Security\CSRF\CsrfTokenGenerator */
	private $csrfTokenGenerator;

	protected function setUp(): void {
		parent::setUp();
		$this->random = $this->getMockBuilder('\OCP\Security\ISecureRandom')
			->disableOriginalConstructor()->getMock();
		$this->csrfTokenGenerator = new CsrfTokenGenerator($this->random);
	}

	public function testGenerateTokenWithCustomNumber(): void {
		$this->random
			->expects($this->once())
			->method('generate')
			->with(3)
			->willReturn('abc');
		$this->assertSame('abc', $this->csrfTokenGenerator->generateToken(3));
	}

	public function testGenerateTokenWithDefault(): void {
		$this->random
			->expects($this->once())
			->method('generate')
			->with(32)
			->willReturn('12345678901234567890123456789012');
		$this->assertSame('12345678901234567890123456789012', $this->csrfTokenGenerator->generateToken(32));
	}
}

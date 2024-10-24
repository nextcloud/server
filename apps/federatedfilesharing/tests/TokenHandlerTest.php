<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\FederatedFileSharing\Tests;

use OCA\FederatedFileSharing\TokenHandler;
use OCP\Security\ISecureRandom;

class TokenHandlerTest extends \Test\TestCase {

	/** @var TokenHandler */
	private $tokenHandler;

	/** @var ISecureRandom | \PHPUnit\Framework\MockObject\MockObject */
	private $secureRandom;

	/** @var int */
	private $expectedTokenLength = 15;

	protected function setUp(): void {
		parent::setUp();

		$this->secureRandom = $this->getMockBuilder(ISecureRandom::class)->getMock();

		$this->tokenHandler = new TokenHandler($this->secureRandom);
	}

	public function testGenerateToken(): void {
		$this->secureRandom->expects($this->once())->method('generate')
			->with(
				$this->expectedTokenLength,
				ISecureRandom::CHAR_ALPHANUMERIC
			)
			->willReturn('mytoken');

		$this->assertSame('mytoken', $this->tokenHandler->generateToken());
	}
}

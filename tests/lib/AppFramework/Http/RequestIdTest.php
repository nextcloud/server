<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OC\AppFramework\Http\RequestId;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class RequestIdTest
 *
 * @package OC\AppFramework\Http
 */
class RequestIdTest extends \Test\TestCase {
	/** @var ISecureRandom|MockObject */
	protected $secureRandom;

	protected function setUp(): void {
		parent::setUp();

		$this->secureRandom = $this->createMock(ISecureRandom::class);
	}

	public function testGetIdWithModUnique(): void {
		$requestId = new RequestId(
			'GeneratedUniqueIdByModUnique',
			$this->secureRandom
		);

		$this->secureRandom->expects($this->never())
			->method('generate');

		$this->assertSame('GeneratedUniqueIdByModUnique', $requestId->getId());
		$this->assertSame('GeneratedUniqueIdByModUnique', $requestId->getId());
	}

	public function testGetIdWithoutModUnique(): void {
		$requestId = new RequestId(
			'',
			$this->secureRandom
		);

		$this->secureRandom->expects($this->once())
			->method('generate')
			->with('20')
			->willReturnOnConsecutiveCalls(
				'GeneratedByNextcloudItself1',
				'GeneratedByNextcloudItself2',
				'GeneratedByNextcloudItself3'
			);

		$this->assertSame('GeneratedByNextcloudItself1', $requestId->getId());
		$this->assertSame('GeneratedByNextcloudItself1', $requestId->getId());
	}
}

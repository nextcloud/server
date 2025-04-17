<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Federation;

use OC\Federation\CloudId;
use OC\Federation\CloudIdManager;
use OCP\Federation\ICloudIdManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @group DB
 */
class CloudIdTest extends TestCase {
	protected CloudIdManager&MockObject $cloudIdManager;

	protected function setUp(): void {
		parent::setUp();

		$this->cloudIdManager = $this->createMock(CloudIdManager::class);
		$this->overwriteService(ICloudIdManager::class, $this->cloudIdManager);
	}

	public function dataGetDisplayCloudId(): array {
		return [
			['test@example.com', 'test', 'example.com', 'test@example.com'],
			['test@http://example.com', 'test', 'http://example.com', 'test@example.com'],
			['test@https://example.com', 'test', 'https://example.com', 'test@example.com'],
			['test@https://example.com', 'test', 'https://example.com', 'Beloved Amy@example.com', 'Beloved Amy'],
		];
	}

	/**
	 * @dataProvider dataGetDisplayCloudId
	 */
	public function testGetDisplayCloudId(string $id, string $user, string $remote, string $display, ?string $addressbookName = null): void {
		$this->cloudIdManager->expects($this->once())
			->method('getDisplayNameFromContact')
			->willReturn($addressbookName);

		$cloudId = new CloudId($id, $user, $remote);
		$this->assertEquals($display, $cloudId->getDisplayId());
	}
}

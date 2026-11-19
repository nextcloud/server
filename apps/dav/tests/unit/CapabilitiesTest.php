<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit;

use OCA\DAV\Capabilities;
use OCA\DAV\Upload\AsyncRequestService;
use OCP\IConfig;
use OCP\User\IAvailabilityCoordinator;
use Test\TestCase;

/**
 * @package OCA\DAV\Tests\unit
 */
class CapabilitiesTest extends TestCase {
	private function createCapabilities(bool $bulkUpload, bool $absence, bool $asyncRequest): Capabilities {
		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('getSystemValueBool')
			->with('bulkupload.enabled', $this->isType('bool'))
			->willReturn($bulkUpload);
		$coordinator = $this->createMock(IAvailabilityCoordinator::class);
		$coordinator->expects($this->once())
			->method('isEnabled')
			->willReturn($absence);
		$asyncRequestService = $this->createMock(AsyncRequestService::class);
		$asyncRequestService->expects($this->once())
			->method('isAvailable')
			->willReturn($asyncRequest);
		return new Capabilities($config, $coordinator, $asyncRequestService);
	}

	public function testGetCapabilities(): void {
		$capabilities = $this->createCapabilities(false, false, false);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
				'public_shares_chunking' => true,
				'search_supports_creation_time' => true,
				'search_supports_upload_time' => true,
				'search_supports_last_activity' => true,
			],
		];
		$this->assertSame($expected, $capabilities->getCapabilities());
	}

	public function testGetCapabilitiesWithBulkUpload(): void {
		$capabilities = $this->createCapabilities(true, false, false);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
				'public_shares_chunking' => true,
				'search_supports_creation_time' => true,
				'search_supports_upload_time' => true,
				'search_supports_last_activity' => true,
				'bulkupload' => '1.0',
			],
		];
		$this->assertSame($expected, $capabilities->getCapabilities());
	}

	public function testGetCapabilitiesWithAbsence(): void {
		$capabilities = $this->createCapabilities(false, true, false);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
				'public_shares_chunking' => true,
				'search_supports_creation_time' => true,
				'search_supports_upload_time' => true,
				'search_supports_last_activity' => true,
				'absence-supported' => true,
				'absence-replacement' => true,
			],
		];
		$this->assertSame($expected, $capabilities->getCapabilities());
	}

	public function testGetCapabilitiesWithAsyncRequest(): void {
		$capabilities = $this->createCapabilities(false, false, true);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
				'public_shares_chunking' => true,
				'search_supports_creation_time' => true,
				'search_supports_upload_time' => true,
				'search_supports_last_activity' => true,
				'async_request' => true,
			],
		];
		$this->assertSame($expected, $capabilities->getCapabilities());
	}
}

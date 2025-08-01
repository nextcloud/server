<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit;

use OCA\DAV\Capabilities;
use OCP\IConfig;
use OCP\User\IAvailabilityCoordinator;
use Test\TestCase;

/**
 * @package OCA\DAV\Tests\unit
 */
class CapabilitiesTest extends TestCase {
	public function testGetCapabilities(): void {
		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('getSystemValueBool')
			->with('bulkupload.enabled', $this->isType('bool'))
			->willReturn(false);
		$coordinator = $this->createMock(IAvailabilityCoordinator::class);
		$coordinator->expects($this->once())
			->method('isEnabled')
			->willReturn(false);
		$capabilities = new Capabilities($config, $coordinator);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
				'public_shares_chunking' => true,
			],
		];
		$this->assertSame($expected, $capabilities->getCapabilities());
	}

	public function testGetCapabilitiesWithBulkUpload(): void {
		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('getSystemValueBool')
			->with('bulkupload.enabled', $this->isType('bool'))
			->willReturn(true);
		$coordinator = $this->createMock(IAvailabilityCoordinator::class);
		$coordinator->expects($this->once())
			->method('isEnabled')
			->willReturn(false);
		$capabilities = new Capabilities($config, $coordinator);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
				'public_shares_chunking' => true,
				'bulkupload' => '1.0',
			],
		];
		$this->assertSame($expected, $capabilities->getCapabilities());
	}

	public function testGetCapabilitiesWithAbsence(): void {
		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('getSystemValueBool')
			->with('bulkupload.enabled', $this->isType('bool'))
			->willReturn(false);
		$coordinator = $this->createMock(IAvailabilityCoordinator::class);
		$coordinator->expects($this->once())
			->method('isEnabled')
			->willReturn(true);
		$capabilities = new Capabilities($config, $coordinator);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
				'public_shares_chunking' => true,
				'absence-supported' => true,
				'absence-replacement' => true,
			],
		];
		$this->assertSame($expected, $capabilities->getCapabilities());
	}
}

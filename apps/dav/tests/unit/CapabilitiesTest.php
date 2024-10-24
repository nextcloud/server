<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit;

use OCA\DAV\Capabilities;
use OCP\IConfig;
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
		$capabilities = new Capabilities($config);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
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
		$capabilities = new Capabilities($config);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
				'bulkupload' => '1.0',
			],
		];
		$this->assertSame($expected, $capabilities->getCapabilities());
	}
}

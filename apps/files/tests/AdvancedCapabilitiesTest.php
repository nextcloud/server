<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files;

use OCA\Files\Service\SettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AdvancedCapabilitiesTest extends TestCase {

	protected SettingsService&MockObject $service;
	protected AdvancedCapabilities $capabilities;

	protected function setUp(): void {
		parent::setUp();
		$this->service = $this->createMock(SettingsService::class);
		$this->capabilities = new AdvancedCapabilities($this->service);
	}

	/**
	 * @dataProvider dataGetCapabilities
	 */
	public function testGetCapabilities(bool $wcf): void {
		$this->service
			->expects(self::once())
			->method('hasFilesWindowsSupport')
			->willReturn($wcf);

		self::assertEqualsCanonicalizing(['files' => [ 'windows_compatible_filenames' => $wcf ]], $this->capabilities->getCapabilities());
	}

	public static function dataGetCapabilities(): array {
		return [
			'WCF enabled' => [
				true,
			],
			'WCF disabled' => [
				false,
			],
		];
	}
}

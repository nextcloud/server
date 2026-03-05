<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\Federation\CalendarFederationConfig;
use OCP\AppFramework\Services\IAppConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CalendarFederationConfigTest extends TestCase {
	private CalendarFederationConfig $config;

	private IAppConfig&MockObject $appConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->config = new CalendarFederationConfig(
			$this->appConfig,
		);
	}

	public static function provideIsFederationEnabledData(): array {
		return [
			[true],
			[false],
		];
	}

	#[DataProvider(methodName: 'provideIsFederationEnabledData')]
	public function testIsFederationEnabled(bool $configValue): void {
		$this->appConfig->expects(self::once())
			->method('getAppValueBool')
			->with('enableCalendarFederation', true)
			->willReturn($configValue);

		$this->assertEquals($configValue, $this->config->isFederationEnabled());
	}
}

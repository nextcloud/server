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
use Test\FakeFrameworkAppConfig;
use Test\TestCase;

class CalendarFederationConfigTest extends TestCase {
	private CalendarFederationConfig $config;

	private IAppConfig $appConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = new FakeFrameworkAppConfig('dav');
		$this->config = new CalendarFederationConfig($this->appConfig);
	}

	public static function provideIsFederationEnabledData(): array {
		return [
			[true],
			[false],
		];
	}

	#[DataProvider(methodName: 'provideIsFederationEnabledData')]
	public function testIsFederationEnabled(bool $configValue): void {
		$this->appConfig->setAppValueBool('enableCalendarFederation', $configValue);
		$this->assertEquals($configValue, $this->config->isFederationEnabled());
	}
}

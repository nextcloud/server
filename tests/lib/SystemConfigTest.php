<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\Config;
use OC\SystemConfig;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SystemConfigTest
 *
 * @package Test
 */
class SystemConfigTest extends TestCase {
	private Config&MockObject $config;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(Config::class);
	}

	public function testGetFilteredValueMasksTheEuroOfficeSecret(): void {
		$this->config->method('getValue')
			->willReturnMap([
				['config_extra_sensitive_values', [], []],
				['eurooffice', '', [
					'editors_check_interval' => 0,
					'jwt_secret' => 'a-document-server-signing-key',
					'jwt_header' => 'AuthorizationJwt',
				]],
			]);

		$systemConfig = new SystemConfig($this->config);

		$this->assertSame([
			'editors_check_interval' => 0,
			'jwt_secret' => IConfig::SENSITIVE_VALUE,
			'jwt_header' => 'AuthorizationJwt',
		], $systemConfig->getFilteredValue('eurooffice'));
	}
}

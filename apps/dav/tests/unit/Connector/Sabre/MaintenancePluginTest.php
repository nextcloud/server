<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class MaintenancePluginTest
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class MaintenancePluginTest extends TestCase {
	private IConfig&MockObject $config;
	private IL10N&MockObject $l10n;
	private MaintenancePlugin $maintenancePlugin;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->maintenancePlugin = new MaintenancePlugin($this->config, $this->l10n);
	}


	public function testMaintenanceMode(): void {
		$this->expectException(\Sabre\DAV\Exception\ServiceUnavailable::class);
		$this->expectExceptionMessage('System is in maintenance mode.');

		$this->config
			->expects($this->exactly(1))
			->method('getSystemValueBool')
			->with('maintenance')
			->willReturn(true);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->maintenancePlugin->checkMaintenanceMode();
	}
}

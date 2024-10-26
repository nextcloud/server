<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCP\IConfig;
use OCP\IL10N;
use Test\TestCase;

/**
 * Class MaintenancePluginTest
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class MaintenancePluginTest extends TestCase {
	/** @var IConfig */
	private $config;
	/** @var \PHPUnit\Framework\MockObject\Builder\InvocationMocker|\PHPUnit_Framework_MockObject_Builder_InvocationMocker|IL10N */
	private $l10n;
	/** @var MaintenancePlugin */
	private $maintenancePlugin;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();
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

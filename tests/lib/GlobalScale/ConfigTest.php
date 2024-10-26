<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\GlobalScale;

use OC\GlobalScale\Config;
use OCP\IConfig;
use Test\TestCase;

class ConfigTest extends TestCase {
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
	}

	/**
	 * @param array $mockMethods
	 * @return Config|\PHPUnit\Framework\MockObject\MockObject
	 */
	public function getInstance($mockMethods = []) {
		if (!empty($mockMethods)) {
			return $this->getMockBuilder(Config::class)
				->setConstructorArgs([$this->config])
				->setMethods($mockMethods)
				->getMock();
		}

		return new Config($this->config);
	}

	public function testIsGlobalScaleEnabled(): void {
		$gsConfig = $this->getInstance();
		$this->config->expects($this->once())->method('getSystemValueBool')
			->with('gs.enabled', false)->willReturn(true);

		$result = $gsConfig->isGlobalScaleEnabled();

		$this->assertTrue($result);
	}


	/**
	 * @dataProvider dataTestOnlyInternalFederation
	 *
	 * @param bool $gsEnabled
	 * @param string $gsFederation
	 * @param bool $expected
	 */
	public function testOnlyInternalFederation($gsEnabled, $gsFederation, $expected): void {
		$gsConfig = $this->getInstance(['isGlobalScaleEnabled']);

		$gsConfig->expects($this->any())->method('isGlobalScaleEnabled')->willReturn($gsEnabled);

		$this->config->expects($this->any())->method('getSystemValueString')
			->with('gs.federation', 'internal')->willReturn($gsFederation);

		$this->assertSame($expected, $gsConfig->onlyInternalFederation());
	}

	public function dataTestOnlyInternalFederation() {
		return [
			[true, 'global', false],
			[true, 'internal', true],
			[false, 'global', false],
			[false, 'internal', false]
		];
	}
}

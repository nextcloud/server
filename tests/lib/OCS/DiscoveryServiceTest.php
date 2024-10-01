<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\OCS;

use OC\OCS\DiscoveryService;
use OCP\Http\Client\IClientService;
use OCP\ICacheFactory;
use OCP\OCS\IDiscoveryService;
use Test\TestCase;

class DiscoveryServiceTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject | ICacheFactory */
	private $cacheFactory;

	/** @var \PHPUnit\Framework\MockObject\MockObject | IClientService */
	private $clientService;

	/** @var IDiscoveryService */
	private $discoveryService;

	protected function setUp(): void {
		parent::setUp();

		$this->cacheFactory = $this->getMockBuilder(ICacheFactory::class)->getMock();
		$this->clientService = $this->getMockBuilder(IClientService::class)->getMock();

		$this->discoveryService = new DiscoveryService(
			$this->cacheFactory,
			$this->clientService
		);
	}

	/**
	 * @dataProvider dataTestIsSafeUrl
	 *
	 * @param string $url
	 * @param bool $expected
	 */
	public function testIsSafeUrl($url, $expected): void {
		$result = $this->invokePrivate($this->discoveryService, 'isSafeUrl', [$url]);
		$this->assertSame($expected, $result);
	}

	public function dataTestIsSafeUrl() {
		return [
			['api/ocs/v1.php/foo', true],
			['/api/ocs/v1.php/foo', true],
			['api/ocs/v1.php/foo/', true],
			['api/ocs/v1.php/foo-bar/', true],
			['api/ocs/v1:php/foo', false],
			['api/ocs/<v1.php/foo', false],
			['api/ocs/v1.php>/foo', false],
		];
	}

	/**
	 * @dataProvider dataTestGetEndpoints
	 *
	 * @param array $decodedServices
	 * @param string $service
	 * @param array $expected
	 */
	public function testGetEndpoints($decodedServices, $service, $expected): void {
		$result = $this->invokePrivate($this->discoveryService, 'getEndpoints', [$decodedServices, $service]);
		$this->assertSame($expected, $result);
	}

	public function dataTestGetEndpoints() {
		return [
			[['services' => ['myService' => ['endpoints' => []]]], 'myService', []],
			[['services' => ['myService' => ['endpoints' => ['foo' => '/bar']]]], 'myService', ['foo' => '/bar']],
			[['services' => ['myService' => ['endpoints' => ['foo' => '/bar']]]], 'anotherService', []],
			[['services' => ['myService' => ['endpoints' => ['foo' => '/bar</foo']]]], 'myService', []],
		];
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\DummyGetResponsePlugin;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

/**
 * Class DummyGetResponsePluginTest
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class DummyGetResponsePluginTest extends TestCase {
	/** @var DummyGetResponsePlugin */
	private $dummyGetResponsePlugin;

	protected function setUp(): void {
		parent::setUp();

		$this->dummyGetResponsePlugin = new DummyGetResponsePlugin();
	}

	public function testInitialize(): void {
		/** @var Server $server */
		$server = $this->getMockBuilder(Server::class)
			->disableOriginalConstructor()
			->getMock();
		$server
			->expects($this->once())
			->method('on')
			->with('method:GET', [$this->dummyGetResponsePlugin, 'httpGet'], 200);

		$this->dummyGetResponsePlugin->initialize($server);
	}


	public function testHttpGet(): void {
		/** @var \Sabre\HTTP\RequestInterface $request */
		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		/** @var \Sabre\HTTP\ResponseInterface $response */
		$response = $server = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$response
			->expects($this->once())
			->method('setBody');
		$response
			->expects($this->once())
			->method('setStatus')
			->with(200);

		$this->assertSame(false, $this->dummyGetResponsePlugin->httpGet($request, $response));
	}
}

<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\DummyGetResponsePlugin;
use Test\TestCase;

/**
 * Class DummyGetResponsePluginTest
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class DummyGetResponsePluginTest extends TestCase {
	/** @var DummyGetResponsePlugin */
	private $dummyGetResponsePlugin;

	public function setUp() {
		parent::setUp();

		$this->dummyGetResponsePlugin = new DummyGetResponsePlugin();
	}

	public function testInitialize() {
		/** @var \Sabre\DAV\Server $server */
		$server = $this->getMockBuilder('\Sabre\DAV\Server')
			->disableOriginalConstructor()
			->getMock();
		$server
			->expects($this->once())
			->method('on')
			->with('method:GET', [$this->dummyGetResponsePlugin, 'httpGet'], 200);

		$this->dummyGetResponsePlugin->initialize($server);
	}


	public function testHttpGet() {
		/** @var \Sabre\HTTP\RequestInterface $request */
		$request = $this->getMockBuilder('\Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();
		/** @var \Sabre\HTTP\ResponseInterface $response */
		$response = $server = $this->getMockBuilder('\Sabre\HTTP\ResponseInterface')
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

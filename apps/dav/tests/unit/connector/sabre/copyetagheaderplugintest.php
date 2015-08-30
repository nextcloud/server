<?php

namespace Tests\Connector\Sabre;

/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class CopyEtagPluginTest extends \Test\TestCase {

	/**
	 * @var \OCA\DAV\Connector\Sabre\CopyEtagHeaderPlugin
	 */
	private $plugin;

	public function setUp() {
		parent::setUp();
		$this->server = new \Sabre\DAV\Server();
		$this->plugin = new \OCA\DAV\Connector\Sabre\CopyEtagHeaderPlugin();
		$this->plugin->initialize($this->server);
	}

	public function testCopyEtag() {
		$request = new \Sabre\Http\Request();
		$response = new \Sabre\Http\Response();
		$response->setHeader('Etag', 'abcd');

		$this->plugin->afterMethod($request, $response);

		$this->assertEquals('abcd', $response->getHeader('OC-Etag'));
	}

	public function testNoopWhenEmpty() {
		$request = new \Sabre\Http\Request();
		$response = new \Sabre\Http\Response();

		$this->plugin->afterMethod($request, $response);

		$this->assertNull($response->getHeader('OC-Etag'));
	}
}

<?php

/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class Test_OC_Connector_Sabre_QuotaPlugin extends \Test\TestCase {

	/**
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var \OCA\DAV\Connector\Sabre\QuotaPlugin
	 */
	private $plugin;

	private function init($quota) {
		$view = $this->buildFileViewMock($quota);
		$this->server = new \Sabre\DAV\Server();
		$this->plugin = new \OCA\DAV\Connector\Sabre\QuotaPlugin($view);
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider lengthProvider
	 */
	public function testLength($expected, $headers) {
		$this->init(0);
		$this->server->httpRequest = new \Sabre\HTTP\Request(null, null, $headers);
		$length = $this->plugin->getLength();
		$this->assertEquals($expected, $length);
	}

	/**
	 * @dataProvider quotaOkayProvider
	 */
	public function testCheckQuota($quota, $headers) {
		$this->init($quota);

		$this->server->httpRequest = new \Sabre\HTTP\Request(null, null, $headers);
		$result = $this->plugin->checkQuota('');
		$this->assertTrue($result);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\InsufficientStorage
	 * @dataProvider quotaExceededProvider
	 */
	public function testCheckExceededQuota($quota, $headers) {
		$this->init($quota);

		$this->server->httpRequest = new \Sabre\HTTP\Request(null, null, $headers);
		$this->plugin->checkQuota('');
	}

	public function quotaOkayProvider() {
		return array(
			array(1024, array()),
			array(1024, array('X-EXPECTED-ENTITY-LENGTH' => '1024')),
			array(1024, array('CONTENT-LENGTH' => '512')),
			array(1024, array('OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512')),
			// \OCP\Files\FileInfo::SPACE-UNKNOWN = -2
			array(-2, array()),
			array(-2, array('X-EXPECTED-ENTITY-LENGTH' => '1024')),
			array(-2, array('CONTENT-LENGTH' => '512')),
			array(-2, array('OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512')),
		);
	}

	public function quotaExceededProvider() {
		return array(
			array(1023, array('X-EXPECTED-ENTITY-LENGTH' => '1024')),
			array(511, array('CONTENT-LENGTH' => '512')),
			array(2047, array('OC-TOTAL-LENGTH' => '2048', 'CONTENT-LENGTH' => '1024')),
		);
	}

	public function lengthProvider() {
		return array(
			array(null, array()),
			array(1024, array('X-EXPECTED-ENTITY-LENGTH' => '1024')),
			array(512, array('CONTENT-LENGTH' => '512')),
			array(2048, array('OC-TOTAL-LENGTH' => '2048', 'CONTENT-LENGTH' => '1024')),
			array(4096, array('OC-TOTAL-LENGTH' => '2048', 'X-EXPECTED-ENTITY-LENGTH' => '4096')),
		);
	}

	private function buildFileViewMock($quota) {
		// mock filesysten
		$view = $this->getMock('\OC\Files\View', array('free_space'), array(), '', false);
		$view->expects($this->any())
			->method('free_space')
			->with($this->identicalTo(''))
			->will($this->returnValue($quota));

		return $view;
	}

}

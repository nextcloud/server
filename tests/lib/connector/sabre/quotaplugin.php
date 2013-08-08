<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_OC_Connector_Sabre_QuotaPlugin extends PHPUnit_Framework_TestCase {

	/**
	 * @var Sabre_DAV_Server
	 */
	private $server;

	/**
	 * @var OC_Connector_Sabre_QuotaPlugin
	 */
	private $plugin;

	public function setUp() {
		$this->server = new Sabre_DAV_Server();
		$this->plugin = new OC_Connector_Sabre_QuotaPlugin();
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider lengthProvider
	 */
	public function testLength($expected, $headers)
	{
		$this->server->httpRequest = new Sabre_HTTP_Request($headers);
		$length = $this->plugin->getLength();
		$this->assertEquals($expected, $length);
	}

	public function lengthProvider()
	{
		return array(
			array(null, array()),
			array(1024, array('HTTP_X_EXPECTED_ENTITY_LENGTH' => '1024')),
			array(512, array('HTTP_CONTENT_LENGTH' => '512')),
			array(2048, array('HTTP_OC_TOTAL_LENGTH' => '2048', 'HTTP_CONTENT_LENGTH' => '1024')),
		);
	}

}

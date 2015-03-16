<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class TestHTTPHelper extends \Test\TestCase {

	/** @var \OCP\IConfig*/
	private $config;
	/** @var \OC\HTTPHelper */
	private $httpHelperMock;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$clientService = $this->getMock('\OCP\Http\Client\IClientService');
		$this->httpHelperMock = $this->getMockBuilder('\OC\HTTPHelper')
			->setConstructorArgs(array($this->config, $clientService))
			->setMethods(array('getHeaders'))
			->getMock();
	}

	public function isHttpTestData() {
		return array(
			array('http://wwww.owncloud.org/enterprise/', true),
			array('https://wwww.owncloud.org/enterprise/', true),
			array('HTTPS://WWW.OWNCLOUD.ORG', true),
			array('HTTP://WWW.OWNCLOUD.ORG', true),
			array('FILE://WWW.OWNCLOUD.ORG', false),
			array('file://www.owncloud.org', false),
			array('FTP://WWW.OWNCLOUD.ORG', false),
			array('ftp://www.owncloud.org', false),
		);
	}

	/**
	 * @dataProvider isHttpTestData
	 */
	public function testIsHTTP($url, $expected) {
			$this->assertSame($expected, $this->httpHelperMock->isHTTPURL($url));
	}
}

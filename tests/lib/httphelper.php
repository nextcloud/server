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
	/** @var \OC\Security\CertificateManager */
	private $certificateManager;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->certificateManager = $this->getMock('\OCP\ICertificateManager');
		$this->httpHelperMock = $this->getMockBuilder('\OC\HTTPHelper')
			->setConstructorArgs(array($this->config, $this->certificateManager))
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

	/**
	 * @dataProvider postParameters
	 */
	public function testAssemblePostParameters($parameterList, $expectedResult) {
		$helper = \OC::$server->getHTTPHelper();
		$result = \Test_Helper::invokePrivate($helper, 'assemblePostParameters', array($parameterList));
		$this->assertSame($expectedResult, $result);
	}

	public function postParameters() {
		return array(
			array(array('k1' => 'v1'), 'k1=v1'),
			array(array('k1' => 'v1', 'k2' => 'v2'), 'k1=v1&k2=v2'),
			array(array(), ''),
		);
	}
}

<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class TestHTTPHelper extends \Test\TestCase {

	/** @var \OC\AllConfig*/
	private $config;
	/** @var \OC\HTTPHelper */
	private $httpHelperMock;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('\OC\AllConfig')
			->disableOriginalConstructor()->getMock();
		$this->httpHelperMock = $this->getMockBuilder('\OC\HTTPHelper')
			->setConstructorArgs(array($this->config))
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
	 * Note: Not using a dataprovider because onConsecutiveCalls expects not
	 * an array but the function arguments directly
	 */
	public function testGetFinalLocationOfURLValid() {
		$url = 'https://www.owncloud.org/enterprise/';
		$expected = 'https://www.owncloud.com/enterprise/';
		$this->httpHelperMock->expects($this->any())
			->method('getHeaders')
			->will($this->onConsecutiveCalls(
				array('Location' => 'http://www.owncloud.com/enterprise/'),
				array('Location' => 'https://www.owncloud.com/enterprise/')
			));
		$result = $this->httpHelperMock->getFinalLocationOfURL($url);
		$this->assertSame($expected, $result);
	}

	/**
	 * Note: Not using a dataprovider because onConsecutiveCalls expects not
	 * an array but the function arguments directly
	 */
	public function testGetFinalLocationOfURLInvalid() {
		$url = 'https://www.owncloud.org/enterprise/';
		$expected = 'http://www.owncloud.com/enterprise/';
		$this->httpHelperMock->expects($this->any())
			->method('getHeaders')
			->will($this->onConsecutiveCalls(
				array('Location' => 'http://www.owncloud.com/enterprise/'),
				array('Location' => 'file://etc/passwd'),
				array('Location' => 'http://www.example.com/')
			));
		$result = $this->httpHelperMock->getFinalLocationOfURL($url);
		$this->assertSame($expected, $result);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage URL must begin with HTTPS or HTTP.
	 */
	public function testGetFinalLocationOfURLException() {
		$this->httpHelperMock->getFinalLocationOfURL('file://etc/passwd');
	}

	/**
	 * @dataProvider isHttpTestData
	 */
	public function testIsHTTP($url, $expected) {
			$this->assertSame($expected, $this->httpHelperMock->isHTTPURL($url));
	}

}

<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

class HTTPHelperTest extends \Test\TestCase {

	/** @var \OCP\IConfig*/
	private $config;
	/** @var \OC\HTTPHelper */
	private $httpHelperMock;
	/** @var \OCP\Http\Client\IClientService */
	private $clientService;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->clientService = $this->getMock('\OCP\Http\Client\IClientService');
		$this->httpHelperMock = $this->getMockBuilder('\OC\HTTPHelper')
			->setConstructorArgs(array($this->config, $this->clientService))
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

	public function testPostSuccess() {
		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));
		$response = $this->getMockBuilder('\OCP\Http\Client\IResponse')
			->disableOriginalConstructor()->getMock();
		$client
			->expects($this->once())
			->method('post')
			->with(
				'https://owncloud.org',
				[
					'body' => [
						'Foo' => 'Bar',
					],
					'connect_timeout' => 10,

				]
			)
			->will($this->returnValue($response));
		$response
			->expects($this->once())
			->method('getBody')
			->will($this->returnValue('Body of the requested page'));


		$response = $this->httpHelperMock->post('https://owncloud.org', ['Foo' => 'Bar']);
		$expected = [
			'success' => true,
			'result' => 'Body of the requested page'
		];
		$this->assertSame($expected, $response);
	}

	public function testPostException() {
		$client = $this->getMockBuilder('\OCP\Http\Client\IClient')
			->disableOriginalConstructor()->getMock();
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->will($this->returnValue($client));
		$client
			->expects($this->once())
			->method('post')
			->with(
				'https://owncloud.org',
				[
					'body' => [
						'Foo' => 'Bar',
					],
					'connect_timeout' => 10,

				]
			)
			->will($this->throwException(new \Exception('Something failed')));


		$response = $this->httpHelperMock->post('https://owncloud.org', ['Foo' => 'Bar']);
		$expected = [
			'success' => false,
			'result' => 'Something failed'
		];
		$this->assertSame($expected, $response);
	}

}

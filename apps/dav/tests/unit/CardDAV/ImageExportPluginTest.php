<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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


namespace OCA\DAV\Tests\unit\CardDAV;


use OCA\DAV\CardDAV\ImageExportPlugin;
use OCP\ILogger;
use Sabre\CardDAV\Card;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class ImageExportPluginTest extends TestCase {

	/** @var ResponseInterface | \PHPUnit_Framework_MockObject_MockObject */
	private $response;
	/** @var RequestInterface | \PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var ImageExportPlugin | \PHPUnit_Framework_MockObject_MockObject */
	private $plugin;
	/** @var Server */
	private $server;
	/** @var Tree | \PHPUnit_Framework_MockObject_MockObject */
	private $tree;
	/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')->getMock();
		$this->response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')->getMock();
		$this->server = $this->getMockBuilder('Sabre\DAV\Server')->getMock();
		$this->tree = $this->getMockBuilder('Sabre\DAV\Tree')->disableOriginalConstructor()->getMock();
		$this->server->tree = $this->tree;
		$this->logger = $this->getMockBuilder('\OCP\ILogger')->getMock();

		$this->plugin = $this->getMockBuilder('OCA\DAV\CardDAV\ImageExportPlugin')
			->setMethods(['getPhoto'])
			->setConstructorArgs([$this->logger])
			->getMock();
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider providesQueryParams
	 * @param $param
	 */
	public function testQueryParams($param) {
		$this->request->expects($this->once())->method('getQueryParameters')->willReturn($param);
		$result = $this->plugin->httpGet($this->request, $this->response);
		$this->assertTrue($result);
	}

	public function providesQueryParams() {
		return [
			[[]],
			[['1']],
			[['foo' => 'bar']],
		];
	}

	public function testNotACard() {
		$this->request->expects($this->once())->method('getQueryParameters')->willReturn(['photo' => true]);
		$this->request->expects($this->once())->method('getPath')->willReturn('/files/welcome.txt');
		$this->tree->expects($this->once())->method('getNodeForPath')->with('/files/welcome.txt')->willReturn(null);
		$result = $this->plugin->httpGet($this->request, $this->response);
		$this->assertTrue($result);
	}

	/**
	 * @dataProvider providesCardWithOrWithoutPhoto
	 * @param bool $expected
	 * @param array $getPhotoResult
	 */
	public function testCardWithOrWithoutPhoto($expected, $getPhotoResult) {
		$this->request->expects($this->once())->method('getQueryParameters')->willReturn(['photo' => true]);
		$this->request->expects($this->once())->method('getPath')->willReturn('/files/welcome.txt');

		$card = $this->getMockBuilder('Sabre\CardDAV\Card')->disableOriginalConstructor()->getMock();
		$this->tree->expects($this->once())->method('getNodeForPath')->with('/files/welcome.txt')->willReturn($card);

		$this->plugin->expects($this->once())->method('getPhoto')->willReturn($getPhotoResult);

		if (!$expected) {
			$this->response
				->expects($this->at(0))
				->method('setHeader')
				->with('Content-Type', $getPhotoResult['Content-Type']);
			$this->response
				->expects($this->at(1))
				->method('setHeader')
				->with('Content-Disposition', 'attachment');
			$this->response
				->expects($this->once())
				->method('setStatus');
			$this->response
				->expects($this->once())
				->method('setBody');
		}

		$result = $this->plugin->httpGet($this->request, $this->response);
		$this->assertEquals($expected, $result);
	}

	public function providesCardWithOrWithoutPhoto() {
		return [
			[true, null],
			[false, ['Content-Type' => 'image/jpeg', 'body' => '1234']],
		];
	}

	/**
	 * @dataProvider providesPhotoData
	 * @param $expected
	 * @param $cardData
	 */
	public function testGetPhoto($expected, $cardData) {
		/** @var Card | \PHPUnit_Framework_MockObject_MockObject $card */
		$card = $this->getMockBuilder('Sabre\CardDAV\Card')->disableOriginalConstructor()->getMock();
		$card->expects($this->once())->method('get')->willReturn($cardData);

		$this->plugin = new ImageExportPlugin($this->logger);
		$this->plugin->initialize($this->server);

		$result = $this->plugin->getPhoto($card);
		$this->assertEquals($expected, $result);
	}

	public function providesPhotoData() {
		return [
			'empty vcard' => [
				false,
				''
			],
			'vcard without PHOTO' => [
				false,
				"BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nEND:VCARD\r\n"
			],
			'vcard 3 with PHOTO' => [
				[
					'Content-Type' => 'image/jpeg',
					'body' => '12345'
				],
				"BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nPHOTO;ENCODING=b;TYPE=JPEG:MTIzNDU=\r\nEND:VCARD\r\n"
			],
			'vcard 3 with PHOTO URL' => [
				false,
				"BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nPHOTO;TYPE=JPEG;VALUE=URI:http://example.com/photo.jpg\r\nEND:VCARD\r\n"
			],
			'vcard 4 with PHOTO' => [
				[
					'Content-Type' => 'image/jpeg',
					'body' => '12345'
				],
				"BEGIN:VCARD\r\nVERSION:4.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nPHOTO:data:image/jpeg;base64,MTIzNDU=\r\nEND:VCARD\r\n"
			],
			'vcard 4 with PHOTO URL' => [
				false,
				"BEGIN:VCARD\r\nVERSION:4.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nPHOTO;MEDIATYPE=image/jpeg:http://example.org/photo.jpg\r\nEND:VCARD\r\n"
			],
			'vcard 4 with PHOTO AND INVALID MIMEtYPE' => [
				[
					'Content-Type' => 'application/octet-stream',
					'body' => '12345'
				],
				"BEGIN:VCARD\r\nVERSION:4.0\r\nPRODID:-//Sabre//Sabre VObject 3.5.0//EN\r\nUID:12345\r\nFN:12345\r\nN:12345;;;;\r\nPHOTO:data:image/svg;base64,MTIzNDU=\r\nEND:VCARD\r\n"
			],
		];
	}
}

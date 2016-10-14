<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class QuotaPluginTest extends \Test\TestCase {

	/**
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var \OCA\DAV\Connector\Sabre\QuotaPlugin
	 */
	private $plugin;

	private function init($quota, $checkedPath = '') {
		$view = $this->buildFileViewMock($quota, $checkedPath);
		$this->server = new \Sabre\DAV\Server();
		$this->plugin = $this->getMockBuilder('\OCA\DAV\Connector\Sabre\QuotaPlugin')
			->setConstructorArgs([$view])
			->setMethods(['getFileChunking'])
			->getMock();
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider lengthProvider
	 */
	public function testLength($expected, $headers) {
		$this->init(0);
		$this->plugin->expects($this->never())
			->method('getFileChunking');
		$this->server->httpRequest = new \Sabre\HTTP\Request(null, null, $headers);
		$length = $this->plugin->getLength();
		$this->assertEquals($expected, $length);
	}

	/**
	 * @dataProvider quotaOkayProvider
	 */
	public function testCheckQuota($quota, $headers) {
		$this->init($quota);
		$this->plugin->expects($this->never())
			->method('getFileChunking');

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
		$this->plugin->expects($this->never())
			->method('getFileChunking');

		$this->server->httpRequest = new \Sabre\HTTP\Request(null, null, $headers);
		$this->plugin->checkQuota('');
	}

	/**
	 * @dataProvider quotaOkayProvider
	 */
	public function testCheckQuotaOnPath($quota, $headers) {
		$this->init($quota, 'sub/test.txt');
		$this->plugin->expects($this->never())
			->method('getFileChunking');

		$this->server->httpRequest = new \Sabre\HTTP\Request(null, null, $headers);
		$result = $this->plugin->checkQuota('/sub/test.txt');
		$this->assertTrue($result);
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
			[null, ['X-EXPECTED-ENTITY-LENGTH' => 'A']],
			[null, ['CONTENT-LENGTH' => 'A']],
			[1024, ['OC-TOTAL-LENGTH' => 'A', 'CONTENT-LENGTH' => '1024']],
			[1024, ['OC-TOTAL-LENGTH' => 'A', 'X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[null, ['OC-TOTAL-LENGTH' => '2048', 'X-EXPECTED-ENTITY-LENGTH' => 'A']],
			[null, ['OC-TOTAL-LENGTH' => '2048', 'CONTENT-LENGTH' => 'A']],
		);
	}

	public function quotaChunkedOkProvider() {
		return array(
			array(1024, 0, array('X-EXPECTED-ENTITY-LENGTH' => '1024')),
			array(1024, 0, array('CONTENT-LENGTH' => '512')),
			array(1024, 0, array('OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512')),
			// with existing chunks (allowed size = total length - chunk total size)
			array(400, 128, array('X-EXPECTED-ENTITY-LENGTH' => '512')),
			array(400, 128, array('CONTENT-LENGTH' => '512')),
			array(400, 128, array('OC-TOTAL-LENGTH' => '512', 'CONTENT-LENGTH' => '500')),
			// \OCP\Files\FileInfo::SPACE-UNKNOWN = -2
			array(-2, 0, array('X-EXPECTED-ENTITY-LENGTH' => '1024')),
			array(-2, 0, array('CONTENT-LENGTH' => '512')),
			array(-2, 0, array('OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512')),
			array(-2, 128, array('X-EXPECTED-ENTITY-LENGTH' => '1024')),
			array(-2, 128, array('CONTENT-LENGTH' => '512')),
			array(-2, 128, array('OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512')),
		);
	}

	/**
	 * @dataProvider quotaChunkedOkProvider
	 */
	public function testCheckQuotaChunkedOk($quota, $chunkTotalSize, $headers) {
		$this->init($quota, 'sub/test.txt');

		$mockChunking = $this->getMockBuilder('\OC_FileChunking')
			->disableOriginalConstructor()
			->getMock();
		$mockChunking->expects($this->once())
			->method('getCurrentSize')
			->will($this->returnValue($chunkTotalSize));

		$this->plugin->expects($this->once())
			->method('getFileChunking')
			->will($this->returnValue($mockChunking));

		$headers['OC-CHUNKED'] = 1;
		$this->server->httpRequest = new \Sabre\HTTP\Request(null, null, $headers);
		$result = $this->plugin->checkQuota('/sub/test.txt-chunking-12345-3-1');
		$this->assertTrue($result);
	}

	public function quotaChunkedFailProvider() {
		return array(
			array(400, 0, array('X-EXPECTED-ENTITY-LENGTH' => '1024')),
			array(400, 0, array('CONTENT-LENGTH' => '512')),
			array(400, 0, array('OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512')),
			// with existing chunks (allowed size = total length - chunk total size)
			array(380, 128, array('X-EXPECTED-ENTITY-LENGTH' => '512')),
			array(380, 128, array('CONTENT-LENGTH' => '512')),
			array(380, 128, array('OC-TOTAL-LENGTH' => '512', 'CONTENT-LENGTH' => '500')),
		);
	}

	/**
	 * @dataProvider quotaChunkedFailProvider
	 * @expectedException \Sabre\DAV\Exception\InsufficientStorage
	 */
	public function testCheckQuotaChunkedFail($quota, $chunkTotalSize, $headers) {
		$this->init($quota, 'sub/test.txt');

		$mockChunking = $this->getMockBuilder('\OC_FileChunking')
			->disableOriginalConstructor()
			->getMock();
		$mockChunking->expects($this->once())
			->method('getCurrentSize')
			->will($this->returnValue($chunkTotalSize));

		$this->plugin->expects($this->once())
			->method('getFileChunking')
			->will($this->returnValue($mockChunking));

		$headers['OC-CHUNKED'] = 1;
		$this->server->httpRequest = new \Sabre\HTTP\Request(null, null, $headers);
		$this->plugin->checkQuota('/sub/test.txt-chunking-12345-3-1');
	}

	private function buildFileViewMock($quota, $checkedPath) {
		// mock filesysten
		$view = $this->getMockBuilder('\OC\Files\View')
			->setMethods(['free_space'])
			->disableOriginalConstructor()
			->getMock();
		$view->expects($this->any())
			->method('free_space')
			->with($this->identicalTo($checkedPath))
			->will($this->returnValue($quota));

		return $view;
	}

}

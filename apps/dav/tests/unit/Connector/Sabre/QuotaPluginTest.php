<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\Files\View;
use OC_FileChunking;
use OCA\DAV\Connector\Sabre\QuotaPlugin;
use OCP\Files\FileInfo;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\InsufficientStorage;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\Server;
use Sabre\HTTP\Request;
use Test\TestCase;

/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class QuotaPluginTest extends TestCase {

	/** @var Server | MockObject */
	private $server;

	/** @var QuotaPlugin | MockObject */
	private $plugin;

	private function init($quota, $checkedPath = '') {
		$view = $this->buildFileViewMock($quota, $checkedPath);
		$this->server = new Server();
		$this->plugin = $this->getMockBuilder(QuotaPlugin::class)
			->setConstructorArgs([$view])
			->onlyMethods(['getFileChunking'])
			->getMock();
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider lengthProvider
	 */
	public function testLength(?int $expected, array $headers) {
		$this->init(0);
		$this->plugin->expects($this->never())
			->method('getFileChunking');
		$this->server->httpRequest = new Request('POST', 'dummy.file', $headers);
		$length = $this->plugin->getLength();
		$this->assertEquals($expected, $length);
	}

	/**
	 * @dataProvider quotaOkayProvider
	 * @throws InsufficientStorage|ServiceUnavailable
	 */
	public function testCheckQuota(int $quota, array $headers) {
		$this->init($quota);
		$this->plugin->expects($this->never())
			->method('getFileChunking');

		$this->server->httpRequest = new Request('POST', 'dummy.file', $headers);
		$result = $this->plugin->checkQuota('');
		$this->assertTrue($result);
	}

	/**
	 * @dataProvider quotaExceededProvider
	 * @throws InsufficientStorage|ServiceUnavailable
	 */
	public function testCheckExceededQuota(int $quota, array $headers) {
		$this->expectException(InsufficientStorage::class);

		$this->init($quota);
		$this->plugin->expects($this->never())
			->method('getFileChunking');

		$this->server->httpRequest = new Request('POST', 'dummy.file', $headers);
		$this->plugin->checkQuota('');
	}

	/**
	 * @dataProvider quotaOkayProvider
	 * @throws InsufficientStorage|ServiceUnavailable
	 */
	public function testCheckQuotaOnPath(int $quota, array $headers) {
		$this->init($quota, 'sub/test.txt');
		$this->plugin->expects($this->never())
			->method('getFileChunking');

		$this->server->httpRequest = new Request('POST', 'dummy.file', $headers);
		$result = $this->plugin->checkQuota('/sub/test.txt');
		$this->assertTrue($result);
	}

	public function quotaOkayProvider(): array {
		return [
			[1024, []],
			[1024, ['X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[1024, ['CONTENT-LENGTH' => '512']],
			[1024, ['OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512']],

			[FileInfo::SPACE_UNKNOWN, []],
			[FileInfo::SPACE_UNKNOWN, ['X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[FileInfo::SPACE_UNKNOWN, ['CONTENT-LENGTH' => '512']],
			[FileInfo::SPACE_UNKNOWN, ['OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512']],

			[FileInfo::SPACE_UNLIMITED, []],
			[FileInfo::SPACE_UNLIMITED, ['X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[FileInfo::SPACE_UNLIMITED, ['CONTENT-LENGTH' => '512']],
			[FileInfo::SPACE_UNLIMITED, ['OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512']],
		];
	}

	public function quotaExceededProvider(): array {
		return [
			[1023, ['X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[511, ['CONTENT-LENGTH' => '512']],
			[2047, ['OC-TOTAL-LENGTH' => '2048', 'CONTENT-LENGTH' => '1024']],
		];
	}

	public function lengthProvider(): array {
		return [
			[null, []],
			[1024, ['X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[512, ['CONTENT-LENGTH' => '512']],
			[2048, ['OC-TOTAL-LENGTH' => '2048', 'CONTENT-LENGTH' => '1024']],
			[4096, ['OC-TOTAL-LENGTH' => '2048', 'X-EXPECTED-ENTITY-LENGTH' => '4096']],
			[null, ['X-EXPECTED-ENTITY-LENGTH' => 'A']],
			[null, ['CONTENT-LENGTH' => 'A']],
			[1024, ['OC-TOTAL-LENGTH' => 'A', 'CONTENT-LENGTH' => '1024']],
			[1024, ['OC-TOTAL-LENGTH' => 'A', 'X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[null, ['OC-TOTAL-LENGTH' => '2048', 'X-EXPECTED-ENTITY-LENGTH' => 'A']],
			[null, ['OC-TOTAL-LENGTH' => '2048', 'CONTENT-LENGTH' => 'A']],
		];
	}

	public function quotaChunkedOkProvider(): array {
		return [
			[1024, 0, ['X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[1024, 0, ['CONTENT-LENGTH' => '512']],
			[1024, 0, ['OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512']],
			// with existing chunks (allowed size = total length - chunk total size)
			[400, 128, ['X-EXPECTED-ENTITY-LENGTH' => '512']],
			[400, 128, ['CONTENT-LENGTH' => '512']],
			[400, 128, ['OC-TOTAL-LENGTH' => '512', 'CONTENT-LENGTH' => '500']],
			// \OCP\Files\FileInfo::SPACE-UNKNOWN = -2
			[-2, 0, ['X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[-2, 0, ['CONTENT-LENGTH' => '512']],
			[-2, 0, ['OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512']],
			[-2, 128, ['X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[-2, 128, ['CONTENT-LENGTH' => '512']],
			[-2, 128, ['OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512']],
		];
	}

	/**
	 * @dataProvider quotaChunkedOkProvider
	 * @throws InsufficientStorage|ServiceUnavailable
	 */
	public function testCheckQuotaChunkedOk(int $quota, int $chunkTotalSize, array $headers) {
		$this->init($quota, 'sub/test.txt');

		$mockChunking = $this->createMock(OC_FileChunking::class);
		$mockChunking->expects($this->once())
			->method('getCurrentSize')
			->willReturn($chunkTotalSize);

		$this->plugin->expects($this->once())
			->method('getFileChunking')
			->willReturn($mockChunking);

		$headers['OC-CHUNKED'] = 1;
		$this->server->httpRequest = new Request('POST', 'dummy.file', $headers);
		$result = $this->plugin->checkQuota('/sub/test.txt-chunking-12345-3-1');
		$this->assertTrue($result);
	}

	public function quotaChunkedFailProvider(): array {
		return [
			[400, 0, ['X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[400, 0, ['CONTENT-LENGTH' => '512']],
			[400, 0, ['OC-TOTAL-LENGTH' => '1024', 'CONTENT-LENGTH' => '512']],
			// with existing chunks (allowed size = total length - chunk total size)
			[380, 128, ['X-EXPECTED-ENTITY-LENGTH' => '512']],
			[380, 128, ['CONTENT-LENGTH' => '512']],
			[380, 128, ['OC-TOTAL-LENGTH' => '512', 'CONTENT-LENGTH' => '500']],
		];
	}

	/**
	 * @dataProvider quotaChunkedFailProvider
	 * @throws ServiceUnavailable
	 */
	public function testCheckQuotaChunkedFail(int $quota, int $chunkTotalSize, array $headers) {
		$this->expectException(InsufficientStorage::class);

		$this->init($quota, 'sub/test.txt');

		$mockChunking = $this->createMock(OC_FileChunking::class);
		$mockChunking->expects($this->once())
			->method('getCurrentSize')
			->willReturn($chunkTotalSize);

		$this->plugin->expects($this->once())
			->method('getFileChunking')
			->willReturn($mockChunking);

		$headers['OC-CHUNKED'] = 1;
		$this->server->httpRequest = new Request('POST', 'dummy.file', $headers);
		$this->plugin->checkQuota('/sub/test.txt-chunking-12345-3-1');
	}

	private function buildFileViewMock($quota, $checkedPath) {
		// mock filesysten
		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['free_space'])
			->disableOriginalConstructor()
			->getMock();
		$view->expects($this->any())
			->method('free_space')
			->with($this->identicalTo($checkedPath))
			->willReturn($quota);

		return $view;
	}
}

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
use OCA\DAV\Connector\Sabre\QuotaPlugin;
use OCP\Files\FileInfo;
use Test\TestCase;

/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class QuotaPluginTest extends TestCase {

	/** @var \Sabre\DAV\Server | \PHPUnit\Framework\MockObject\MockObject */
	private $server;

	/** @var \OCA\DAV\Connector\Sabre\QuotaPlugin | \PHPUnit\Framework\MockObject\MockObject */
	private $plugin;

	private function init($quota, $checkedPath = ''): void {
		$view = $this->buildFileViewMock($quota, $checkedPath);
		$this->server = new \Sabre\DAV\Server();
		$this->plugin = new QuotaPlugin($view);
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider lengthProvider
	 */
	public function testLength($expected, $headers): void {
		$this->init(0);
		$this->server->httpRequest = new \Sabre\HTTP\Request('POST', 'dummy.file', $headers);
		$length = $this->plugin->getLength();
		$this->assertEquals($expected, $length);
	}

	/**
	 * @dataProvider quotaOkayProvider
	 */
	public function testCheckQuota($quota, $headers): void {
		$this->init($quota);

		$this->server->httpRequest = new \Sabre\HTTP\Request('POST', 'dummy.file', $headers);
		$result = $this->plugin->checkQuota('');
		$this->assertTrue($result);
	}

	/**
	 * @dataProvider quotaExceededProvider
	 */
	public function testCheckExceededQuota($quota, $headers): void {
		$this->expectException(\Sabre\DAV\Exception\InsufficientStorage::class);

		$this->init($quota);

		$this->server->httpRequest = new \Sabre\HTTP\Request('POST', 'dummy.file', $headers);
		$this->plugin->checkQuota('');
	}

	/**
	 * @dataProvider quotaOkayProvider
	 */
	public function testCheckQuotaOnPath($quota, $headers): void {
		$this->init($quota, 'sub/test.txt');

		$this->server->httpRequest = new \Sabre\HTTP\Request('POST', 'dummy.file', $headers);
		$result = $this->plugin->checkQuota('/sub/test.txt');
		$this->assertTrue($result);
	}

	public function quotaOkayProvider() {
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

	public function quotaExceededProvider() {
		return [
			[1023, ['X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[511, ['CONTENT-LENGTH' => '512']],
			[2047, ['OC-TOTAL-LENGTH' => '2048', 'CONTENT-LENGTH' => '1024']],
		];
	}

	public function lengthProvider() {
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

	private function buildFileViewMock($quota, $checkedPath) {
		// mock filesysten
		$view = $this->getMockBuilder(View::class)
			->setMethods(['free_space'])
			->disableOriginalConstructor()
			->getMock();
		$view->expects($this->any())
			->method('free_space')
			->with($this->identicalTo($checkedPath))
			->willReturn($quota);

		return $view;
	}
}

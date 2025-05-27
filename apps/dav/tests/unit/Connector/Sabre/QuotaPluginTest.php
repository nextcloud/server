<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\Files\View;
use OCA\DAV\Connector\Sabre\QuotaPlugin;
use OCP\Files\FileInfo;
use Test\TestCase;

class QuotaPluginTest extends TestCase {
	private \Sabre\DAV\Server $server;

	private QuotaPlugin $plugin;

	private function init(int $quota, string $checkedPath = ''): void {
		$view = $this->buildFileViewMock((string)$quota, $checkedPath);
		$this->server = new \Sabre\DAV\Server();
		$this->plugin = new QuotaPlugin($view);
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider lengthProvider
	 */
	public function testLength(?int $expected, array $headers): void {
		$this->init(0);

		$this->server->httpRequest = new \Sabre\HTTP\Request('POST', 'dummy.file', $headers);
		$length = $this->plugin->getLength();
		$this->assertEquals($expected, $length);
	}

	/**
	 * @dataProvider quotaOkayProvider
	 */
	public function testCheckQuota(int $quota, array $headers): void {
		$this->init($quota);

		$this->server->httpRequest = new \Sabre\HTTP\Request('POST', 'dummy.file', $headers);
		$result = $this->plugin->checkQuota('');
		$this->assertTrue($result);
	}

	/**
	 * @dataProvider quotaExceededProvider
	 */
	public function testCheckExceededQuota(int $quota, array $headers): void {
		$this->expectException(\Sabre\DAV\Exception\InsufficientStorage::class);

		$this->init($quota);

		$this->server->httpRequest = new \Sabre\HTTP\Request('POST', 'dummy.file', $headers);
		$this->plugin->checkQuota('');
	}

	/**
	 * @dataProvider quotaOkayProvider
	 */
	public function testCheckQuotaOnPath(int $quota, array $headers): void {
		$this->init($quota, 'sub/test.txt');

		$this->server->httpRequest = new \Sabre\HTTP\Request('POST', 'dummy.file', $headers);
		$result = $this->plugin->checkQuota('/sub/test.txt');
		$this->assertTrue($result);
	}

	public static function quotaOkayProvider(): array {
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

	public static function quotaExceededProvider(): array {
		return [
			[1023, ['X-EXPECTED-ENTITY-LENGTH' => '1024']],
			[511, ['CONTENT-LENGTH' => '512']],
			[2047, ['OC-TOTAL-LENGTH' => '2048', 'CONTENT-LENGTH' => '1024']],
		];
	}

	public static function lengthProvider(): array {
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
			[2048, ['OC-TOTAL-LENGTH' => '2048', 'X-EXPECTED-ENTITY-LENGTH' => 'A']],
			[2048, ['OC-TOTAL-LENGTH' => '2048', 'CONTENT-LENGTH' => 'A']],
		];
	}

	public static function quotaChunkedOkProvider(): array {
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

	public static function quotaChunkedFailProvider(): array {
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

	private function buildFileViewMock(string $quota, string $checkedPath): View {
		// mock filesystem
		$view = $this->getMockBuilder(View::class)
			->onlyMethods(['free_space'])
			->disableOriginalConstructor()
			->getMock();
		$view->expects($this->any())
			->method('free_space')
			->with($checkedPath)
			->willReturn($quota);

		return $view;
	}
}

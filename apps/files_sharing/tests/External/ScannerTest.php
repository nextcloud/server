<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests\External;

use OC\Files\Cache\Cache;
use OCA\Files_Sharing\External\Scanner;
use OCA\Files_Sharing\External\Storage;
use Test\TestCase;

/**
 * @group DB
 */
class ScannerTest extends TestCase {
	protected Scanner $scanner;
	/** @var Storage|\PHPUnit\Framework\MockObject\MockObject */
	protected $storage;
	/** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
	protected $cache;

	protected function setUp(): void {
		parent::setUp();

		$this->storage = $this->getMockBuilder('\OCA\Files_Sharing\External\Storage')
			->disableOriginalConstructor()
			->getMock();
		$this->cache = $this->getMockBuilder('\OC\Files\Cache\Cache')
			->disableOriginalConstructor()
			->getMock();
		$this->storage->expects($this->any())
			->method('getCache')
			->willReturn($this->cache);

		$this->scanner = new Scanner($this->storage);
	}

	public function testScan(): void {
		$this->storage->expects($this->any())
			->method('getShareInfo')
			->willReturn(['status' => 'success', 'data' => []]);

		// FIXME add real tests, we are currently only checking for
		// Declaration of OCA\Files_Sharing\External\Scanner::*() should be
		// compatible with OC\Files\Cache\Scanner::*()
		$this->scanner->scan('test', Scanner::SCAN_RECURSIVE);
		$this->addToAssertionCount(1);
	}

	public function testScanFile(): void {
		// FIXME add real tests, we are currently only checking for
		// Declaration of OCA\Files_Sharing\External\Scanner::*() should be
		// compatible with OC\Files\Cache\Scanner::*()
		$this->scanner->scanFile('test', Scanner::SCAN_RECURSIVE);
		$this->addToAssertionCount(1);
	}
}

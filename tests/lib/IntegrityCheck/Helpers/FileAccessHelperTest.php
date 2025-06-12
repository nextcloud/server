<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\IntegrityCheck\Helpers;

use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OCP\ITempManager;
use OCP\Server;
use Test\TestCase;

class FileAccessHelperTest extends TestCase {
	/** @var FileAccessHelper */
	private $fileAccessHelper;

	protected function setUp(): void {
		parent::setUp();
		$this->fileAccessHelper = new FileAccessHelper();
	}

	public function testReadAndWrite(): void {
		$tempManager = Server::get(ITempManager::class);
		$filePath = $tempManager->getTemporaryFile();
		$data = 'SomeDataGeneratedByIntegrityCheck';

		$this->fileAccessHelper->file_put_contents($filePath, $data);
		$this->assertSame($data, $this->fileAccessHelper->file_get_contents($filePath));
	}

	
	public function testFile_put_contentsWithException(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Failed to write into /anabsolutelynotexistingfolder/on/the/system.txt');

		$this->fileAccessHelper->file_put_contents('/anabsolutelynotexistingfolder/on/the/system.txt', 'MyFiles');
	}

	public function testIs_writable(): void {
		$this->assertFalse($this->fileAccessHelper->is_writable('/anabsolutelynotexistingfolder/on/the/system.txt'));
		$this->assertTrue($this->fileAccessHelper->is_writable(Server::get(ITempManager::class)->getTemporaryFile('MyFile')));
	}

	
	public function testAssertDirectoryExistsWithException(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Directory /anabsolutelynotexistingfolder/on/the/system does not exist.');

		$this->fileAccessHelper->assertDirectoryExists('/anabsolutelynotexistingfolder/on/the/system');
	}

	public function testAssertDirectoryExists(): void {
		$this->fileAccessHelper->assertDirectoryExists(Server::get(ITempManager::class)->getTemporaryFolder('/testfolder/'));
		$this->addToAssertionCount(1);
	}
}

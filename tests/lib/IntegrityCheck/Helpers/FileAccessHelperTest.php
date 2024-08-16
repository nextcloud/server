<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\IntegrityCheck\Helpers;

use OC\IntegrityCheck\Helpers\FileAccessHelper;
use Test\TestCase;

class FileAccessHelperTest extends TestCase {
	/** @var FileAccessHelper */
	private $fileAccessHelper;

	protected function setUp(): void {
		parent::setUp();
		$this->fileAccessHelper = new FileAccessHelper();
	}

	public function testReadAndWrite() {
		$tempManager = \OC::$server->getTempManager();
		$filePath = $tempManager->getTemporaryFile();
		$data = 'SomeDataGeneratedByIntegrityCheck';

		$this->fileAccessHelper->file_put_contents($filePath, $data);
		$this->assertSame($data, $this->fileAccessHelper->file_get_contents($filePath));
	}

	
	public function testFile_put_contentsWithException() {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Failed to write into /anabsolutelynotexistingfolder/on/the/system.txt');

		$this->fileAccessHelper->file_put_contents('/anabsolutelynotexistingfolder/on/the/system.txt', 'MyFiles');
	}

	public function testIs_writable() {
		$this->assertFalse($this->fileAccessHelper->is_writable('/anabsolutelynotexistingfolder/on/the/system.txt'));
		$this->assertTrue($this->fileAccessHelper->is_writable(\OC::$server->getTempManager()->getTemporaryFile('MyFile')));
	}

	
	public function testAssertDirectoryExistsWithException() {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Directory /anabsolutelynotexistingfolder/on/the/system does not exist.');

		$this->fileAccessHelper->assertDirectoryExists('/anabsolutelynotexistingfolder/on/the/system');
	}

	public function testAssertDirectoryExists() {
		$this->fileAccessHelper->assertDirectoryExists(\OC::$server->getTempManager()->getTemporaryFolder('/testfolder/'));
		$this->addToAssertionCount(1);
	}
}

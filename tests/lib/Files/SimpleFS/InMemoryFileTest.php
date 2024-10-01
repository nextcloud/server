<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\File\SimpleFS;

use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\InMemoryFile;
use Test\TestCase;

/**
 * This class provide test casesf or the InMemoryFile.
 *
 * @package Test\File\SimpleFS
 */
class InMemoryFileTest extends TestCase {
	/**
	 * Holds a pdf file with know attributes for tests.
	 *
	 * @var InMemoryFile
	 */
	private $testPdf;

	/**
	 * Sets the test file from "./resources/test.pdf".
	 *
	 * @before
	 * @return void
	 */
	public function setupTestPdf() {
		$fileContents = file_get_contents(
			__DIR__ . '/../../../data/test.pdf'
		);
		$this->testPdf = new InMemoryFile('test.pdf', $fileContents);
	}

	/**
	 * Asserts that putContent replaces the file contents.
	 *
	 * @return void
	 */
	public function testPutContent(): void {
		$this->testPdf->putContent('test');
		self::assertEquals('test', $this->testPdf->getContent());
	}

	/**
	 * Asserts that delete() doesn't rise an exception.
	 *
	 * @return void
	 */
	public function testDelete(): void {
		$this->testPdf->delete();
		// assert true, otherwise phpunit complains about not doing any assert
		self::assertTrue(true);
	}

	/**
	 * Asserts that getName returns the name passed on file creation.
	 *
	 * @return void
	 */
	public function testGetName(): void {
		self::assertEquals('test.pdf', $this->testPdf->getName());
	}

	/**
	 * Asserts that the file size is the size of the test file.
	 *
	 * @return void
	 */
	public function testGetSize(): void {
		self::assertEquals(7083, $this->testPdf->getSize());
	}

	/**
	 * Asserts the file contents are the same than the original file contents.
	 *
	 * @return void
	 */
	public function testGetContent(): void {
		self::assertEquals(
			file_get_contents(__DIR__ . '/../../../data/test.pdf'),
			$this->testPdf->getContent()
		);
	}

	/**
	 * Asserts the test file modification time is an integer.
	 *
	 * @return void
	 */
	public function testGetMTime(): void {
		self::assertTrue(is_int($this->testPdf->getMTime()));
	}

	/**
	 * Asserts the test file mime type is "application/json".
	 *
	 * @return void
	 */
	public function testGetMimeType(): void {
		self::assertEquals('application/pdf', $this->testPdf->getMimeType());
	}


	/**
	 * Asserts that read() raises an NotPermittedException.
	 *
	 * @return void
	 */
	public function testRead(): void {
		self::expectException(NotPermittedException::class);
		$this->testPdf->read();
	}

	/**
	 * Asserts that write() raises an NotPermittedException.
	 *
	 * @return void
	 */
	public function testWrite(): void {
		self::expectException(NotPermittedException::class);
		$this->testPdf->write();
	}
}

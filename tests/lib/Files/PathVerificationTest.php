<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files;

use OC\Files\Storage\Local;
use OC\Files\View;
use OCP\Files\InvalidPathException;

/**
 * Class PathVerificationTest
 *
 * @group DB
 *
 * @package Test\Files
 */
class PathVerificationTest extends \Test\TestCase {
	/**
	 * @var \OC\Files\View
	 */
	private $view;

	protected function setUp(): void {
		parent::setUp();
		$this->view = new View();
	}


	public function testPathVerificationFileNameTooLong(): void {
		$this->expectException(\OCP\Files\InvalidPathException::class);
		$this->expectExceptionMessage('Filename is too long');

		$fileName = str_repeat('a', 500);
		$this->view->verifyPath('', $fileName);
	}


	/**
	 * @dataProvider providesEmptyFiles
	 */
	public function testPathVerificationEmptyFileName($fileName): void {
		$this->expectException(\OCP\Files\InvalidPathException::class);
		$this->expectExceptionMessage('Empty filename is not allowed');

		$this->view->verifyPath('', $fileName);
	}

	public function providesEmptyFiles() {
		return [
			[''],
			[' '],
		];
	}

	/**
	 * @dataProvider providesDotFiles
	 */
	public function testPathVerificationDotFiles($fileName): void {
		$this->expectException(\OCP\Files\InvalidPathException::class);
		$this->expectExceptionMessage('Dot files are not allowed');

		$this->view->verifyPath('', $fileName);
	}

	public function providesDotFiles() {
		return [
			['.'],
			['..'],
			[' .'],
			[' ..'],
			['. '],
			['.. '],
			[' . '],
			[' .. '],
		];
	}

	/**
	 * @dataProvider providesAstralPlane
	 */
	public function testPathVerificationAstralPlane($fileName): void {
		$connection = \OC::$server->getDatabaseConnection();

		if (!$connection->supports4ByteText()) {
			$this->expectException(InvalidPathException::class);
			$this->expectExceptionMessage('File name contains at least one invalid character');
		} else {
			$this->addToAssertionCount(1);
		}

		$this->view->verifyPath('', $fileName);
	}

	public function providesAstralPlane() {
		return [
			// this is the monkey emoji - http://en.wikipedia.org/w/index.php?title=%F0%9F%90%B5&redirect=no
			['🐵'],
			['🐵.txt'],
			['txt.💩'],
			['💩🐵.txt'],
			['💩🐵'],
		];
	}

	/**
	 * @dataProvider providesValidPosixPaths
	 */
	public function testPathVerificationValidPaths($fileName): void {
		$storage = new Local(['datadir' => '']);

		self::invokePrivate($storage, 'verifyPosixPath', [$fileName]);
		// nothing thrown
		$this->addToAssertionCount(1);
	}

	public function providesValidPosixPaths() {
		return [
			['simple'],
			['simple.txt'],
			['\''],
			['`'],
			['%'],
			['()'],
			['[]'],
			['!'],
			['$'],
			['_'],
		];
	}
}

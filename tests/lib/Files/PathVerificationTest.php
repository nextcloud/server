<?php
/**
 * Copyright (c) 2015 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file. */

namespace Test\Files;

use OC\Files\Storage\Local;
use OC\Files\View;

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

	protected function setUp() {
		parent::setUp();
		$this->view = new View();
	}

	/**
	 * @expectedException \OCP\Files\InvalidPathException
	 * @expectedExceptionMessage File name is too long
	 */
	public function testPathVerificationFileNameTooLong() {
		$fileName = str_repeat('a', 500);
		$this->view->verifyPath('', $fileName);
	}


	/**
	 * @dataProvider providesEmptyFiles
	 * @expectedException \OCP\Files\InvalidPathException
	 * @expectedExceptionMessage Empty filename is not allowed
	 */
	public function testPathVerificationEmptyFileName($fileName) {
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
	 * @expectedException \OCP\Files\InvalidPathException
	 * @expectedExceptionMessage Dot files are not allowed
	 */
	public function testPathVerificationDotFiles($fileName) {
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
	 * @expectedException \OCP\Files\InvalidPathException
	 * @expectedExceptionMessage 4-byte characters are not supported in file names
	 */
	public function testPathVerificationAstralPlane($fileName) {
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
	 * @dataProvider providesInvalidCharsPosix
	 * @expectedException \OCP\Files\InvalidCharacterInPathException
	 */
	public function testPathVerificationInvalidCharsPosix($fileName) {
		$storage = new Local(['datadir' => '']);

		$fileName = " 123{$fileName}456 ";
		self::invokePrivate($storage, 'verifyPosixPath', [$fileName]);
	}

	public function providesInvalidCharsPosix() {
		return [
			[\chr(0)],
			[\chr(1)],
			[\chr(2)],
			[\chr(3)],
			[\chr(4)],
			[\chr(5)],
			[\chr(6)],
			[\chr(7)],
			[\chr(8)],
			[\chr(9)],
			[\chr(10)],
			[\chr(11)],
			[\chr(12)],
			[\chr(13)],
			[\chr(14)],
			[\chr(15)],
			[\chr(16)],
			[\chr(17)],
			[\chr(18)],
			[\chr(19)],
			[\chr(20)],
			[\chr(21)],
			[\chr(22)],
			[\chr(23)],
			[\chr(24)],
			[\chr(25)],
			[\chr(26)],
			[\chr(27)],
			[\chr(28)],
			[\chr(29)],
			[\chr(30)],
			[\chr(31)],
			['/'],
			['\\'],
		];
	}

	/**
	 * @dataProvider providesValidPosixPaths
	 */
	public function testPathVerificationValidPaths($fileName) {
		$storage = new Local(['datadir' => '']);

		self::invokePrivate($storage, 'verifyPosixPath', [$fileName]);
		// nothing thrown
		$this->assertTrue(true);
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

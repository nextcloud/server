<?php
/**
 * Copyright (c) 2012 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC_Helper;

class LegacyHelperTest extends \Test\TestCase {

	/**
	 * @dataProvider humanFileSizeProvider
	 */
	public function testHumanFileSize($expected, $input)
	{
		$result = OC_Helper::humanFileSize($input);
		$this->assertEquals($expected, $result);
	}

	public function humanFileSizeProvider()
	{
		return array(
			array('0 B', 0),
			array('1 KB', 1024),
			array('9.5 MB', 10000000),
			array('1.3 GB', 1395864371),
			array('465.7 GB', 500000000000),
			array('454.7 TB', 500000000000000),
			array('444.1 PB', 500000000000000000),
		);
	}

	/**
	 * @dataProvider phpFileSizeProvider
	 */
	public function testPhpFileSize($expected, $input)
	{
		$result = OC_Helper::phpFileSize($input);
		$this->assertEquals($expected, $result);
	}

	public function phpFileSizeProvider()
	{
		return array(
			array('0B', 0),
			array('1K', 1024),
			array('9.5M', 10000000),
			array('1.3G', 1395864371),
			array('465.7G', 500000000000),
			array('465661.3G', 500000000000000),
			array('465661287.3G', 500000000000000000),
		);
	}

	/**
	 * @dataProvider providesComputerFileSize
	 */
	function testComputerFileSize($expected, $input) {
		$result = OC_Helper::computerFileSize($input);
		$this->assertEquals($expected, $result);
	}

	function providesComputerFileSize(){
		return [
			[0.0, "0 B"],
			[1024.0, "1 KB"],
			[1395864371.0, '1.3 GB'],
			[9961472.0, "9.5 MB"],
			[500041567437.0, "465.7 GB"],
			[false, "12 GB etfrhzui"]
		];
	}

	function testIsSubDirectory() {
		$result = OC_Helper::isSubDirectory("./data/", "/anotherDirectory/");
		$this->assertFalse($result);

		$result = OC_Helper::isSubDirectory("./data/", "./data/");
		$this->assertTrue($result);

		mkdir("data/TestSubdirectory", 0777);
		$result = OC_Helper::isSubDirectory("data/TestSubdirectory/", "data");
		rmdir("data/TestSubdirectory");
		$this->assertTrue($result);
	}

	function testMb_array_change_key_case() {
		$arrayStart = array(
			"Foo" => "bar",
			"Bar" => "foo",
			);
		$arrayResult = array(
			"foo" => "bar",
			"bar" => "foo",
			);
		$result = OC_Helper::mb_array_change_key_case($arrayStart);
		$expected = $arrayResult;
		$this->assertEquals($result, $expected);

		$arrayStart = array(
			"foo" => "bar",
			"bar" => "foo",
			);
		$arrayResult = array(
			"FOO" => "bar",
			"BAR" => "foo",
			);
		$result = OC_Helper::mb_array_change_key_case($arrayStart, MB_CASE_UPPER);
		$expected = $arrayResult;
		$this->assertEquals($result, $expected);
	}

	function testRecursiveArraySearch() {
		$haystack = array(
			"Foo" => "own",
			"Bar" => "Cloud",
			);

		$result = OC_Helper::recursiveArraySearch($haystack, "own");
		$expected = "Foo";
		$this->assertEquals($result, $expected);

		$result = OC_Helper::recursiveArraySearch($haystack, "NotFound");
		$this->assertFalse($result);
	}

	function testBuildNotExistingFileNameForView() {
		$viewMock = $this->getMock('\OC\Files\View', array(), array(), '', false);
		$this->assertEquals('/filename', OC_Helper::buildNotExistingFileNameForView('/', 'filename', $viewMock));
		$this->assertEquals('dir/filename.ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename.ext', $viewMock));

		$viewMock->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true)); // filename.ext exists
		$this->assertEquals('dir/filename (2).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename.ext', $viewMock));

		$viewMock->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true)); // filename.ext exists
		$viewMock->expects($this->at(1))
			->method('file_exists')
			->will($this->returnValue(true)); // filename (2).ext exists
		$this->assertEquals('dir/filename (3).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename.ext', $viewMock));

		$viewMock->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true)); // filename (1).ext exists
		$this->assertEquals('dir/filename (2).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename (1).ext', $viewMock));

		$viewMock->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true)); // filename (2).ext exists
		$this->assertEquals('dir/filename (3).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename (2).ext', $viewMock));

		$viewMock->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true)); // filename (2).ext exists
		$viewMock->expects($this->at(1))
			->method('file_exists')
			->will($this->returnValue(true)); // filename (3).ext exists
		$this->assertEquals('dir/filename (4).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename (2).ext', $viewMock));

		$viewMock->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true)); // filename(1).ext exists
		$this->assertEquals('dir/filename(2).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename(1).ext', $viewMock));

		$viewMock->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true)); // filename(1) (1).ext exists
		$this->assertEquals('dir/filename(1) (2).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename(1) (1).ext', $viewMock));

		$viewMock->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true)); // filename(1) (1).ext exists
		$viewMock->expects($this->at(1))
			->method('file_exists')
			->will($this->returnValue(true)); // filename(1) (2).ext exists
		$this->assertEquals('dir/filename(1) (3).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename(1) (1).ext', $viewMock));

		$viewMock->expects($this->at(0))
			->method('file_exists')
			->will($this->returnValue(true)); // filename(1) (2) (3).ext exists
		$this->assertEquals('dir/filename(1) (2) (4).ext', OC_Helper::buildNotExistingFileNameForView('dir', 'filename(1) (2) (3).ext', $viewMock));
	}

	/**
	 * @dataProvider streamCopyDataProvider
	 */
	public function testStreamCopy($expectedCount, $expectedResult, $source, $target) {

		if (is_string($source)) {
			$source = fopen($source, 'r');
		}
		if (is_string($target)) {
			$target = fopen($target, 'w');
		}

		list($count, $result) = \OC_Helper::streamCopy($source, $target);

		if (is_resource($source)) {
			fclose($source);
		}
		if (is_resource($target)) {
			fclose($target);
		}

		$this->assertSame($expectedCount, $count);
		$this->assertSame($expectedResult, $result);
	}


	function streamCopyDataProvider() {
		return array(
			array(0, false, false, false),
			array(0, false, \OC::$SERVERROOT . '/tests/data/lorem.txt', false),
			array(filesize(\OC::$SERVERROOT . '/tests/data/lorem.txt'), true, \OC::$SERVERROOT . '/tests/data/lorem.txt', \OC::$SERVERROOT . '/tests/data/lorem-copy.txt'),
			array(3670, true, \OC::$SERVERROOT . '/tests/data/testimage.png', \OC::$SERVERROOT . '/tests/data/testimage-copy.png'),
		);
	}

	// Url generator methods

	/**
	 * @small
	 * test linkToPublic URL construction
	 */
	public function testLinkToPublic() {
		\OC::$WEBROOT = '';
		$result = \OC_Helper::linkToPublic('files');
		$this->assertEquals('http://localhost/s', $result);
		$result = \OC_Helper::linkToPublic('files', false);
		$this->assertEquals('http://localhost/s', $result);
		$result = \OC_Helper::linkToPublic('files', true);
		$this->assertEquals('http://localhost/s/', $result);

		$result = \OC_Helper::linkToPublic('other');
		$this->assertEquals('http://localhost/public.php?service=other', $result);
		$result = \OC_Helper::linkToPublic('other', false);
		$this->assertEquals('http://localhost/public.php?service=other', $result);
		$result = \OC_Helper::linkToPublic('other', true);
		$this->assertEquals('http://localhost/public.php?service=other/', $result);

		\OC::$WEBROOT = '/owncloud';
		$result = \OC_Helper::linkToPublic('files');
		$this->assertEquals('http://localhost/owncloud/s', $result);
		$result = \OC_Helper::linkToPublic('files', false);
		$this->assertEquals('http://localhost/owncloud/s', $result);
		$result = \OC_Helper::linkToPublic('files', true);
		$this->assertEquals('http://localhost/owncloud/s/', $result);

		$result = \OC_Helper::linkToPublic('other');
		$this->assertEquals('http://localhost/owncloud/public.php?service=other', $result);
		$result = \OC_Helper::linkToPublic('other', false);
		$this->assertEquals('http://localhost/owncloud/public.php?service=other', $result);
		$result = \OC_Helper::linkToPublic('other', true);
		$this->assertEquals('http://localhost/owncloud/public.php?service=other/', $result);
	}

	/**
	 * Tests recursive folder deletion with rmdirr()
	 */
	public function testRecursiveFolderDeletion() {
		$baseDir = \OC::$server->getTempManager()->getTemporaryFolder() . '/';
		mkdir($baseDir . 'a/b/c/d/e', 0777, true);
		mkdir($baseDir . 'a/b/c1/d/e', 0777, true);
		mkdir($baseDir . 'a/b/c2/d/e', 0777, true);
		mkdir($baseDir . 'a/b1/c1/d/e', 0777, true);
		mkdir($baseDir . 'a/b2/c1/d/e', 0777, true);
		mkdir($baseDir . 'a/b3/c1/d/e', 0777, true);
		mkdir($baseDir . 'a1/b', 0777, true);
		mkdir($baseDir . 'a1/c', 0777, true);
		file_put_contents($baseDir . 'a/test.txt', 'Hello file!');
		file_put_contents($baseDir . 'a/b1/c1/test one.txt', 'Hello file one!');
		file_put_contents($baseDir . 'a1/b/test two.txt', 'Hello file two!');
		\OC_Helper::rmdirr($baseDir . 'a');

		$this->assertFalse(file_exists($baseDir . 'a'));
		$this->assertTrue(file_exists($baseDir . 'a1'));

		\OC_Helper::rmdirr($baseDir);
		$this->assertFalse(file_exists($baseDir));
	}

	/**
	 * Allows us to test private methods/properties
	 *
	 * @param $object
	 * @param $methodName
	 * @param array $parameters
	 * @return mixed
	 * @deprecated Please extend \Test\TestCase and use self::invokePrivate() then
	 */
	public static function invokePrivate($object, $methodName, array $parameters = array()) {
		return parent::invokePrivate($object, $methodName, $parameters);
	}
}

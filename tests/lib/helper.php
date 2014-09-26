<?php
/**
 * Copyright (c) 2012 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Helper extends PHPUnit_Framework_TestCase {

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
			array('1 kB', 1024),
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
	 * @dataProvider computerFileSizeProvider
	 */
	function testComputerFileSize($expected, $input) {
		$result = OC_Helper::computerFileSize($input);
		$this->assertEquals($expected, $result);
	}

	function computerFileSizeProvider(){
		return array(
			array(0.0, "0 B"),
			array(1024.0, "1 kB"),
			array(1395864371.0, '1.3 GB'),
			array(9961472.0, "9.5 MB"),
			array(500041567437.0, "465.7 GB"),
		);
	}

	function testGetMimeType() {
		$dir=OC::$SERVERROOT.'/tests/data';
		$result = OC_Helper::getMimeType($dir."/");
		$expected = 'httpd/unix-directory';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::getMimeType($dir."/data.tar.gz");
		$expected = 'application/x-gzip';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::getMimeType($dir."/data.zip");
		$expected = 'application/zip';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::getMimeType($dir."/logo-wide.svg");
		$expected = 'image/svg+xml';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::getMimeType($dir."/logo-wide.png");
		$expected = 'image/png';
		$this->assertEquals($result, $expected);
	}

	function testGetSecureMimeType() {
		$dir=OC::$SERVERROOT.'/tests/data';

		$result = OC_Helper::getSecureMimeType('image/svg+xml');
		$expected = 'text/plain';
		$this->assertEquals($result, $expected);

		$result = OC_Helper::getSecureMimeType('image/png');
		$expected = 'image/png';
		$this->assertEquals($result, $expected);
	}

	function testGetFileNameMimeType() {
		$this->assertEquals('text/plain', OC_Helper::getFileNameMimeType('foo.txt'));
		$this->assertEquals('image/png', OC_Helper::getFileNameMimeType('foo.png'));
		$this->assertEquals('image/png', OC_Helper::getFileNameMimeType('foo.bar.png'));
		$this->assertEquals('application/octet-stream', OC_Helper::getFileNameMimeType('.png'));
		$this->assertEquals('application/octet-stream', OC_Helper::getFileNameMimeType('foo'));
		$this->assertEquals('application/octet-stream', OC_Helper::getFileNameMimeType(''));
	}

	function testGetStringMimeType() {
		$result = OC_Helper::getStringMimeType("/data/data.tar.gz");
		$expected = 'text/plain; charset=us-ascii';
		$this->assertEquals($result, $expected);
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

	function testMb_substr_replace() {
		$result = OC_Helper::mb_substr_replace("This  is a teststring", "string", 5);
		$expected = "This string is a teststring";
		$this->assertEquals($result, $expected);
	}

	function testMb_str_replace() {
		$result = OC_Helper::mb_str_replace("teststring", "string", "This is a teststring");
		$expected = "This is a string";
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
	 * test absolute URL construction
	 * @dataProvider provideDocRootURLs
	 */
	function testMakeAbsoluteURLDocRoot($url, $expectedResult) {
		\OC::$WEBROOT = '';
		$result = \OC_Helper::makeURLAbsolute($url);

		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @small
	 * test absolute URL construction
	 * @dataProvider provideSubDirURLs
	 */
	function testMakeAbsoluteURLSubDir($url, $expectedResult) {
		\OC::$WEBROOT = '/owncloud';
		$result = \OC_Helper::makeURLAbsolute($url);

		$this->assertEquals($expectedResult, $result);
	}

	public function provideDocRootURLs() {
		return array(
			array('index.php', 'http://localhost/index.php'),
			array('/index.php', 'http://localhost/index.php'),
			array('/apps/index.php', 'http://localhost/apps/index.php'),
			array('apps/index.php', 'http://localhost/apps/index.php'),
		);
	}

	public function provideSubDirURLs() {
		return array(
			array('index.php', 'http://localhost/owncloud/index.php'),
			array('/index.php', 'http://localhost/owncloud/index.php'),
			array('/apps/index.php', 'http://localhost/owncloud/apps/index.php'),
			array('apps/index.php', 'http://localhost/owncloud/apps/index.php'),
		);
	}

	/**
	 * @small
	 * test linkTo URL construction
	 * @dataProvider provideDocRootAppUrlParts
	 */
	public function testLinkToDocRoot($app, $file, $args, $expectedResult) {
		\OC::$WEBROOT = '';
		$result = \OC_Helper::linkTo($app, $file, $args);

		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @small
	 * test linkTo URL construction in sub directory
	 * @dataProvider provideSubDirAppUrlParts
	 */
	public function testLinkToSubDir($app, $file, $args, $expectedResult) {
		\OC::$WEBROOT = '/owncloud';
		$result = \OC_Helper::linkTo($app, $file, $args);

		$this->assertEquals($expectedResult, $result);
	}

	public function provideDocRootAppUrlParts() {
		return array(
			array('files', 'index.php', array(), '/index.php/apps/files'),
			array('files', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), '/index.php/apps/files?trut=trat&dut=dat'),
			array('', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), '/index.php?trut=trat&dut=dat'),
		);
	}

	public function provideSubDirAppUrlParts() {
		return array(
			array('files', 'index.php', array(), '/owncloud/index.php/apps/files'),
			array('files', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), '/owncloud/index.php/apps/files?trut=trat&dut=dat'),
			array('', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), '/owncloud/index.php?trut=trat&dut=dat'),
		);
	}

	/**
	 * @small
	 * test linkToAbsolute URL construction
	 * @dataProvider provideDocRootAppAbsoluteUrlParts
	 */
	public function testLinkToAbsoluteDocRoot($app, $file, $args, $expectedResult) {
		\OC::$WEBROOT = '';
		$result = \OC_Helper::linkToAbsolute($app, $file, $args);

		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @small
	 * test linkToAbsolute URL construction in sub directory
	 * @dataProvider provideSubDirAppAbsoluteUrlParts
	 */
	public function testLinkToAbsoluteSubDir($app, $file, $args, $expectedResult) {
		\OC::$WEBROOT = '/owncloud';
		$result = \OC_Helper::linkToAbsolute($app, $file, $args);

		$this->assertEquals($expectedResult, $result);
	}

	public function provideDocRootAppAbsoluteUrlParts() {
		return array(
			array('files', 'index.php', array(), 'http://localhost/index.php/apps/files'),
			array('files', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), 'http://localhost/index.php/apps/files?trut=trat&dut=dat'),
			array('', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), 'http://localhost/index.php?trut=trat&dut=dat'),
		);
	}

	public function provideSubDirAppAbsoluteUrlParts() {
		return array(
			array('files', 'index.php', array(), 'http://localhost/owncloud/index.php/apps/files'),
			array('files', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), 'http://localhost/owncloud/index.php/apps/files?trut=trat&dut=dat'),
			array('', 'index.php', array('trut' => 'trat', 'dut' => 'dat'), 'http://localhost/owncloud/index.php?trut=trat&dut=dat'),
		);
	}

	/**
	 * @small
	 * test linkToRemoteBase URL construction
	 */
	public function testLinkToRemoteBase() {
		\OC::$WEBROOT = '';
		$result = \OC_Helper::linkToRemoteBase('webdav');
		$this->assertEquals('/remote.php/webdav', $result);

		\OC::$WEBROOT = '/owncloud';
		$result = \OC_Helper::linkToRemoteBase('webdav');
		$this->assertEquals('/owncloud/remote.php/webdav', $result);
	}

	/**
	 * @small
	 * test linkToRemote URL construction
	 */
	public function testLinkToRemote() {
		\OC::$WEBROOT = '';
		$result = \OC_Helper::linkToRemote('webdav');
		$this->assertEquals('http://localhost/remote.php/webdav/', $result);
		$result = \OC_Helper::linkToRemote('webdav', false);
		$this->assertEquals('http://localhost/remote.php/webdav', $result);

		\OC::$WEBROOT = '/owncloud';
		$result = \OC_Helper::linkToRemote('webdav');
		$this->assertEquals('http://localhost/owncloud/remote.php/webdav/', $result);
		$result = \OC_Helper::linkToRemote('webdav', false);
		$this->assertEquals('http://localhost/owncloud/remote.php/webdav', $result);
	}

	/**
	 * @small
	 * test linkToPublic URL construction
	 */
	public function testLinkToPublic() {
		\OC::$WEBROOT = '';
		$result = \OC_Helper::linkToPublic('files');
		$this->assertEquals('http://localhost/public.php?service=files', $result);
		$result = \OC_Helper::linkToPublic('files', false);
		$this->assertEquals('http://localhost/public.php?service=files', $result);

		\OC::$WEBROOT = '/owncloud';
		$result = \OC_Helper::linkToPublic('files');
		$this->assertEquals('http://localhost/owncloud/public.php?service=files', $result);
		$result = \OC_Helper::linkToPublic('files', false);
		$this->assertEquals('http://localhost/owncloud/public.php?service=files', $result);
	}

	/**
	 * Tests recursive folder deletion with rmdirr()
	 */
	public function testRecursiveFolderDeletion() {
		$baseDir = \OC_Helper::tmpFolder() . '/';
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
	 */
	public static function invokePrivate($object, $methodName, array $parameters = array()) {
		$reflection = new ReflectionClass(get_class($object));

		if ($reflection->hasMethod($methodName)) {
			$method = $reflection->getMethod($methodName);

			$method->setAccessible(true);

			return $method->invokeArgs($object, $parameters);
		} elseif ($reflection->hasProperty($methodName)) {
			$property = $reflection->getProperty($methodName);

			$property->setAccessible(true);

			return $property->getValue($object);
		}

		return false;
	}
}

<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2012 Robin Appelman icewind@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test;

/**
 * Class StreamWrappersTest
 *
 * @group DB
 */
class StreamWrappersTest extends \Test\TestCase {

	private static $trashBinStatus;

	public static function setUpBeforeClass() {
		self::$trashBinStatus = \OC_App::isEnabled('files_trashbin');
		\OC_App::disable('files_trashbin');
	}

	public static function tearDownAfterClass() {
		if (self::$trashBinStatus) {
			\OC_App::enable('files_trashbin');
		}
	}

	public function testFakeDir() {
		$items = array('foo', 'bar');
		\OC\Files\Stream\Dir::register('test', $items);
		$dh = opendir('fakedir://test');
		$result = array();
		while ($file = readdir($dh)) {
			$result[] = $file;
			$this->assertContains($file, $items);
		}
		$this->assertEquals(count($items), count($result));
	}

	public function testCloseStream() {
		//ensure all basic stream stuff works
		$sourceFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$tmpFile = \OC::$server->getTempManager()->getTemporaryFile('.txt');
		$file = 'close://' . $tmpFile;
		$this->assertTrue(file_exists($file));
		file_put_contents($file, file_get_contents($sourceFile));
		$this->assertEquals(file_get_contents($sourceFile), file_get_contents($file));
		unlink($file);
		clearstatcache();
		$this->assertFalse(file_exists($file));

		//test callback
		$tmpFile = \OC::$server->getTempManager()->getTemporaryFile('.txt');
		$file = 'close://' . $tmpFile;
		$actual = false;
		$callback = function($path) use (&$actual) { $actual = $path; };
		\OC\Files\Stream\Close::registerCallback($tmpFile, $callback);
		$fh = fopen($file, 'w');
		fwrite($fh, 'asd');
		fclose($fh);
		$this->assertSame($tmpFile, $actual);
	}

	public function testOC() {
		// FIXME: use proper tearDown with $this->loginAsUser() and $this->logout()
		// (would currently break the tests for some reason)
		$originalStorage = \OC\Files\Filesystem::getStorage('/');
		\OC\Files\Filesystem::clearMounts();

		$storage = new \OC\Files\Storage\Temporary(array());
		$storage->file_put_contents('foo.txt', 'asd');
		\OC\Files\Filesystem::mount($storage, array(), '/');

		$this->assertTrue(file_exists('oc:///foo.txt'));
		$this->assertEquals('asd', file_get_contents('oc:///foo.txt'));
		$this->assertEquals(array('.', '..', 'foo.txt'), scandir('oc:///'));

		file_put_contents('oc:///bar.txt', 'qwerty');
		$this->assertEquals('qwerty', $storage->file_get_contents('bar.txt'));
		$this->assertEquals(array('.', '..', 'bar.txt', 'foo.txt'), scandir('oc:///'));
		$this->assertEquals('qwerty', file_get_contents('oc:///bar.txt'));

		$fh = fopen('oc:///bar.txt', 'rb');
		$this->assertSame(0, ftell($fh));
		$content = fread($fh, 4);
		$this->assertSame(4, ftell($fh));
		$this->assertSame('qwer', $content);
		$content = fread($fh, 1);
		$this->assertSame(5, ftell($fh));
		$this->assertSame(0, fseek($fh, 0));
		$this->assertSame(0, ftell($fh));

		unlink('oc:///foo.txt');
		$this->assertEquals(array('.', '..', 'bar.txt'), scandir('oc:///'));

		\OC\Files\Filesystem::clearMounts();
		\OC\Files\Filesystem::mount($originalStorage, array(), '/');
	}
}

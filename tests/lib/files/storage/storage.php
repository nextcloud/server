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

namespace Test\Files\Storage;

abstract class Storage extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \OC\Files\Storage\Storage instance
	 */
	protected $instance;
	protected $waitDelay = 0;

	/**
	 * Sleep for the number of seconds specified in the
	 * $waitDelay attribute
	 */
	protected function wait() {
		if ($this->waitDelay > 0) {
			sleep($this->waitDelay);
		}
	}

	/**
	 * the root folder of the storage should always exist, be readable and be recognized as a directory
	 */
	public function testRoot() {
		$this->assertTrue($this->instance->file_exists('/'), 'Root folder does not exist');
		$this->assertTrue($this->instance->isReadable('/'), 'Root folder is not readable');
		$this->assertTrue($this->instance->is_dir('/'), 'Root folder is not a directory');
		$this->assertFalse($this->instance->is_file('/'), 'Root folder is a file');
		$this->assertEquals('dir', $this->instance->filetype('/'));

		//without this, any further testing would be useless, not an actual requirement for filestorage though
		$this->assertTrue($this->instance->isUpdatable('/'), 'Root folder is not writable');
	}

	/**
	 * Check that the test() function works
	 */
	public function testTestFunction() {
		$this->assertTrue($this->instance->test());
	}

	/**
	 * @dataProvider directoryProvider
	 */
	public function testDirectories($directory) {
		$this->assertFalse($this->instance->file_exists('/' . $directory));

		$this->assertTrue($this->instance->mkdir('/' . $directory));

		$this->assertTrue($this->instance->file_exists('/' . $directory));
		$this->assertTrue($this->instance->is_dir('/' . $directory));
		$this->assertFalse($this->instance->is_file('/' . $directory));
		$this->assertEquals('dir', $this->instance->filetype('/' . $directory));
		$this->assertEquals(0, $this->instance->filesize('/' . $directory));
		$this->assertTrue($this->instance->isReadable('/' . $directory));
		$this->assertTrue($this->instance->isUpdatable('/' . $directory));

		$dh = $this->instance->opendir('/');
		$content = array();
		while ($file = readdir($dh)) {
			if ($file != '.' and $file != '..') {
				$content[] = $file;
			}
		}
		$this->assertEquals(array($directory), $content);

		$this->assertFalse($this->instance->mkdir('/' . $directory)); //cant create existing folders
		$this->assertTrue($this->instance->rmdir('/' . $directory));

		$this->wait();
		$this->assertFalse($this->instance->file_exists('/' . $directory));

		$this->assertFalse($this->instance->rmdir('/' . $directory)); //cant remove non existing folders

		$dh = $this->instance->opendir('/');
		$content = array();
		while ($file = readdir($dh)) {
			if ($file != '.' and $file != '..') {
				$content[] = $file;
			}
		}
		$this->assertEquals(array(), $content);
	}

	public function directoryProvider() {
		return array(
			array('folder'),
			array(' folder'),
			array('folder '),
			array('folder with space'),
			array('spéciäl földer'),
		);
	}

	function loremFileProvider() {
		$root = \OC::$SERVERROOT . '/tests/data/';
		return array(
			// small file
			array($root . 'lorem.txt'),
			// bigger file (> 8 KB which is the standard PHP block size)
			array($root . 'lorem-big.txt')
		);
	}

	/**
	 * test the various uses of file_get_contents and file_put_contents
	 *
	 * @dataProvider loremFileProvider
	 */
	public function testGetPutContents($sourceFile) {
		$sourceText = file_get_contents($sourceFile);

		//fill a file with string data
		$this->instance->file_put_contents('/lorem.txt', $sourceText);
		$this->assertFalse($this->instance->is_dir('/lorem.txt'));
		$this->assertEquals($sourceText, $this->instance->file_get_contents('/lorem.txt'), 'data returned from file_get_contents is not equal to the source data');

		//empty the file
		$this->instance->file_put_contents('/lorem.txt', '');
		$this->assertEquals('', $this->instance->file_get_contents('/lorem.txt'), 'file not emptied');
	}

	/**
	 * test various known mimetypes
	 */
	public function testMimeType() {
		$this->assertEquals('httpd/unix-directory', $this->instance->getMimeType('/'));
		$this->assertEquals(false, $this->instance->getMimeType('/non/existing/file'));

		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->instance->file_put_contents('/lorem.txt', file_get_contents($textFile, 'r'));
		$this->assertEquals('text/plain', $this->instance->getMimeType('/lorem.txt'));

		$pngFile = \OC::$SERVERROOT . '/tests/data/logo-wide.png';
		$this->instance->file_put_contents('/logo-wide.png', file_get_contents($pngFile, 'r'));
		$this->assertEquals('image/png', $this->instance->getMimeType('/logo-wide.png'));

		$svgFile = \OC::$SERVERROOT . '/tests/data/logo-wide.svg';
		$this->instance->file_put_contents('/logo-wide.svg', file_get_contents($svgFile, 'r'));
		$this->assertEquals('image/svg+xml', $this->instance->getMimeType('/logo-wide.svg'));
	}

	public function testCopyAndMove() {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->instance->file_put_contents('/source.txt', file_get_contents($textFile));
		$this->instance->copy('/source.txt', '/target.txt');
		$this->assertTrue($this->instance->file_exists('/target.txt'));
		$this->assertEquals($this->instance->file_get_contents('/source.txt'), $this->instance->file_get_contents('/target.txt'));

		$this->instance->rename('/source.txt', '/target2.txt');
		$this->wait();
		$this->assertTrue($this->instance->file_exists('/target2.txt'));
		$this->assertFalse($this->instance->file_exists('/source.txt'));
		$this->assertEquals(file_get_contents($textFile), $this->instance->file_get_contents('/target2.txt'));

		// move to overwrite
		$testContents = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$this->instance->file_put_contents('/target3.txt', $testContents);
		$this->instance->rename('/target2.txt', '/target3.txt');
		$this->assertTrue($this->instance->file_exists('/target3.txt'));
		$this->assertFalse($this->instance->file_exists('/target2.txt'));
		$this->assertEquals(file_get_contents($textFile), $this->instance->file_get_contents('/target3.txt'));
	}

	public function testLocal() {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->instance->file_put_contents('/lorem.txt', file_get_contents($textFile));
		$localFile = $this->instance->getLocalFile('/lorem.txt');
		$this->assertTrue(file_exists($localFile));
		$this->assertEquals(file_get_contents($localFile), file_get_contents($textFile));

		$this->instance->mkdir('/folder');
		$this->instance->file_put_contents('/folder/lorem.txt', file_get_contents($textFile));
		$this->instance->file_put_contents('/folder/bar.txt', 'asd');
		$this->instance->mkdir('/folder/recursive');
		$this->instance->file_put_contents('/folder/recursive/file.txt', 'foo');
		$localFolder = $this->instance->getLocalFolder('/folder');

		$this->assertTrue(is_dir($localFolder));

		// test below require to use instance->getLocalFile because the physical storage might be different
		$localFile = $this->instance->getLocalFile('/folder/lorem.txt');
		$this->assertTrue(file_exists($localFile));
		$this->assertEquals(file_get_contents($localFile), file_get_contents($textFile));

		$localFile = $this->instance->getLocalFile('/folder/bar.txt');
		$this->assertTrue(file_exists($localFile));
		$this->assertEquals(file_get_contents($localFile), 'asd');

		$localFile = $this->instance->getLocalFile('/folder/recursive/file.txt');
		$this->assertTrue(file_exists($localFile));
		$this->assertEquals(file_get_contents($localFile), 'foo');
	}

	public function testStat() {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$ctimeStart = time();
		$this->instance->file_put_contents('/lorem.txt', file_get_contents($textFile));
		$this->assertTrue($this->instance->isReadable('/lorem.txt'));
		$ctimeEnd = time();
		$mTime = $this->instance->filemtime('/lorem.txt');
		$this->assertTrue($this->instance->hasUpdated('/lorem.txt', $ctimeStart - 5));
		$this->assertTrue($this->instance->hasUpdated('/', $ctimeStart - 5));

		// check that ($ctimeStart - 5) <= $mTime <= ($ctimeEnd + 1)
		$this->assertGreaterThanOrEqual(($ctimeStart - 5), $mTime);
		$this->assertLessThanOrEqual(($ctimeEnd + 1), $mTime);
		$this->assertEquals(filesize($textFile), $this->instance->filesize('/lorem.txt'));

		$stat = $this->instance->stat('/lorem.txt');
		//only size and mtime are required in the result
		$this->assertEquals($stat['size'], $this->instance->filesize('/lorem.txt'));
		$this->assertEquals($stat['mtime'], $mTime);

		if ($this->instance->touch('/lorem.txt', 100) !== false) {
			$mTime = $this->instance->filemtime('/lorem.txt');
			$this->assertEquals($mTime, 100);
		}

		$mtimeStart = time();

		$this->instance->unlink('/lorem.txt');
		$this->assertTrue($this->instance->hasUpdated('/', $mtimeStart - 5));
	}

	public function testUnlink() {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->instance->file_put_contents('/lorem.txt', file_get_contents($textFile));

		$this->assertTrue($this->instance->file_exists('/lorem.txt'));

		$this->assertTrue($this->instance->unlink('/lorem.txt'));
		$this->wait();

		$this->assertFalse($this->instance->file_exists('/lorem.txt'));
	}

	public function testFOpen() {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';

		$fh = @$this->instance->fopen('foo', 'r');
		if ($fh) {
			fclose($fh);
		}
		$this->assertFalse($fh);
		$this->assertFalse($this->instance->file_exists('foo'));

		$fh = $this->instance->fopen('foo', 'w');
		fwrite($fh, file_get_contents($textFile));
		fclose($fh);
		$this->assertTrue($this->instance->file_exists('foo'));

		$fh = $this->instance->fopen('foo', 'r');
		$content = stream_get_contents($fh);
		$this->assertEquals(file_get_contents($textFile), $content);
	}

	public function testTouchCreateFile() {
		$this->assertFalse($this->instance->file_exists('foo'));
		// returns true on success
		$this->assertTrue($this->instance->touch('foo'));
		$this->assertTrue($this->instance->file_exists('foo'));
	}

	public function testRecursiveRmdir() {
		$this->instance->mkdir('folder');
		$this->instance->mkdir('folder/bar');
		$this->wait();
		$this->instance->file_put_contents('folder/asd.txt', 'foobar');
		$this->instance->file_put_contents('folder/bar/foo.txt', 'asd');
		$this->assertTrue($this->instance->rmdir('folder'));
		$this->wait();
		$this->assertFalse($this->instance->file_exists('folder/asd.txt'));
		$this->assertFalse($this->instance->file_exists('folder/bar/foo.txt'));
		$this->assertFalse($this->instance->file_exists('folder/bar'));
		$this->assertFalse($this->instance->file_exists('folder'));
	}

	public function testRecursiveUnlink() {
		$this->instance->mkdir('folder');
		$this->instance->mkdir('folder/bar');
		$this->instance->file_put_contents('folder/asd.txt', 'foobar');
		$this->instance->file_put_contents('folder/bar/foo.txt', 'asd');
		$this->assertTrue($this->instance->unlink('folder'));
		$this->wait();
		$this->assertFalse($this->instance->file_exists('folder/asd.txt'));
		$this->assertFalse($this->instance->file_exists('folder/bar/foo.txt'));
		$this->assertFalse($this->instance->file_exists('folder/bar'));
		$this->assertFalse($this->instance->file_exists('folder'));
	}

	public function hashProvider() {
		return array(
			array('Foobar', 'md5'),
			array('Foobar', 'sha1'),
			array('Foobar', 'sha256'),
		);
	}

	/**
	 * @dataProvider hashProvider
	 */
	public function testHash($data, $type) {
		$this->instance->file_put_contents('hash.txt', $data);
		$this->assertEquals(hash($type, $data), $this->instance->hash($type, 'hash.txt'));
		$this->assertEquals(hash($type, $data, true), $this->instance->hash($type, 'hash.txt', true));
	}

	public function testHashInFileName() {
		$this->instance->file_put_contents('#test.txt', 'data');
		$this->assertEquals('data', $this->instance->file_get_contents('#test.txt'));

		$this->instance->mkdir('#foo');
		$this->instance->file_put_contents('#foo/test.txt', 'data');
		$this->assertEquals('data', $this->instance->file_get_contents('#foo/test.txt'));

		$dh = $this->instance->opendir('#foo');
		$content = array();
		while ($file = readdir($dh)) {
			if ($file != '.' and $file != '..') {
				$content[] = $file;
			}
		}

		$this->assertEquals(array('test.txt'), $content);
	}

	public function testCopyOverWriteFile() {
		$this->instance->file_put_contents('target.txt', 'foo');
		$this->instance->file_put_contents('source.txt', 'bar');
		$this->instance->copy('source.txt', 'target.txt');
		$this->assertEquals('bar', $this->instance->file_get_contents('target.txt'));
	}

	public function testRenameOverWriteFile() {
		$this->instance->file_put_contents('target.txt', 'foo');
		$this->instance->file_put_contents('source.txt', 'bar');
		$this->instance->rename('source.txt', 'target.txt');
		$this->assertEquals('bar', $this->instance->file_get_contents('target.txt'));
		$this->assertFalse($this->instance->file_exists('source.txt'));
	}

	public function testRenameDirectory() {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');
		$this->instance->file_put_contents('source/test2.txt', 'qwerty');
		$this->instance->mkdir('source/subfolder');
		$this->instance->file_put_contents('source/subfolder/test.txt', 'bar');
		$this->instance->rename('source', 'target');

		$this->assertFalse($this->instance->file_exists('source'));
		$this->assertFalse($this->instance->file_exists('source/test1.txt'));
		$this->assertFalse($this->instance->file_exists('source/test2.txt'));
		$this->assertFalse($this->instance->file_exists('source/subfolder'));
		$this->assertFalse($this->instance->file_exists('source/subfolder/test.txt'));

		$this->assertTrue($this->instance->file_exists('target'));
		$this->assertTrue($this->instance->file_exists('target/test1.txt'));
		$this->assertTrue($this->instance->file_exists('target/test2.txt'));
		$this->assertTrue($this->instance->file_exists('target/subfolder'));
		$this->assertTrue($this->instance->file_exists('target/subfolder/test.txt'));

		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
		$this->assertEquals('qwerty', $this->instance->file_get_contents('target/test2.txt'));
		$this->assertEquals('bar', $this->instance->file_get_contents('target/subfolder/test.txt'));
	}

	public function testRenameOverWriteDirectory() {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');

		$this->instance->mkdir('target');
		$this->instance->file_put_contents('target/test1.txt', 'bar');
		$this->instance->file_put_contents('target/test2.txt', 'bar');

		$this->instance->rename('source', 'target');

		$this->assertFalse($this->instance->file_exists('source'));
		$this->assertFalse($this->instance->file_exists('source/test1.txt'));
		$this->assertFalse($this->instance->file_exists('target/test2.txt'));
		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
	}

	public function testRenameOverWriteDirectoryOverFile() {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');

		$this->instance->file_put_contents('target', 'bar');

		$this->instance->rename('source', 'target');

		$this->assertFalse($this->instance->file_exists('source'));
		$this->assertFalse($this->instance->file_exists('source/test1.txt'));
		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
	}

	public function testCopyDirectory() {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');
		$this->instance->file_put_contents('source/test2.txt', 'qwerty');
		$this->instance->mkdir('source/subfolder');
		$this->instance->file_put_contents('source/subfolder/test.txt', 'bar');
		$this->instance->copy('source', 'target');

		$this->assertTrue($this->instance->file_exists('source'));
		$this->assertTrue($this->instance->file_exists('source/test1.txt'));
		$this->assertTrue($this->instance->file_exists('source/test2.txt'));
		$this->assertTrue($this->instance->file_exists('source/subfolder'));
		$this->assertTrue($this->instance->file_exists('source/subfolder/test.txt'));

		$this->assertTrue($this->instance->file_exists('target'));
		$this->assertTrue($this->instance->file_exists('target/test1.txt'));
		$this->assertTrue($this->instance->file_exists('target/test2.txt'));
		$this->assertTrue($this->instance->file_exists('target/subfolder'));
		$this->assertTrue($this->instance->file_exists('target/subfolder/test.txt'));

		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
		$this->assertEquals('qwerty', $this->instance->file_get_contents('target/test2.txt'));
		$this->assertEquals('bar', $this->instance->file_get_contents('target/subfolder/test.txt'));
	}

	public function testCopyOverWriteDirectory() {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');

		$this->instance->mkdir('target');
		$this->instance->file_put_contents('target/test1.txt', 'bar');
		$this->instance->file_put_contents('target/test2.txt', 'bar');

		$this->instance->copy('source', 'target');

		$this->assertFalse($this->instance->file_exists('target/test2.txt'));
		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
	}

	public function testCopyOverWriteDirectoryOverFile() {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');

		$this->instance->file_put_contents('target', 'bar');

		$this->instance->copy('source', 'target');

		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
	}

	public function testInstanceOfStorage() {
		$this->assertTrue($this->instance->instanceOfStorage('\OCP\Files\Storage'));
		$this->assertTrue($this->instance->instanceOfStorage(get_class($this->instance)));
		$this->assertFalse($this->instance->instanceOfStorage('\OC'));
	}
}

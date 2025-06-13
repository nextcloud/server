<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use OC\Files\Cache\Watcher;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IWriteStreamStorage;

abstract class Storage extends \Test\TestCase {
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
	public function testRoot(): void {
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
	public function testTestFunction(): void {
		$this->assertTrue($this->instance->test());
	}

	/**
	 * @dataProvider directoryProvider
	 */
	public function testDirectories($directory): void {
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
		$content = [];
		while (($file = readdir($dh)) !== false) {
			if ($file != '.' and $file != '..') {
				$content[] = $file;
			}
		}
		$this->assertEquals([$directory], $content);

		$content = iterator_to_array($this->instance->getDirectoryContent('/'));

		$this->assertCount(1, $content);
		$dirEntry = $content[0];
		unset($dirEntry['scan_permissions']);
		unset($dirEntry['etag']);
		$this->assertLessThanOrEqual(1, abs($dirEntry['mtime'] - $this->instance->filemtime($directory)));
		unset($dirEntry['mtime']);
		unset($dirEntry['storage_mtime']);
		$this->assertEquals([
			'name' => $directory,
			'mimetype' => $this->instance->getMimeType($directory),
			'size' => -1,
			'permissions' => $this->instance->getPermissions($directory),
		], $dirEntry);

		$this->assertFalse($this->instance->mkdir('/' . $directory)); //can't create existing folders
		$this->assertTrue($this->instance->rmdir('/' . $directory));

		$this->wait();
		$this->assertFalse($this->instance->file_exists('/' . $directory));

		$this->assertFalse($this->instance->rmdir('/' . $directory)); //can't remove non existing folders

		$dh = $this->instance->opendir('/');
		$content = [];
		while (($file = readdir($dh)) !== false) {
			if ($file != '.' and $file != '..') {
				$content[] = $file;
			}
		}
		$this->assertEquals([], $content);
	}

	public static function fileNameProvider(): array {
		return [
			['file.txt'],
			[' file.txt'],
			['folder .txt'],
			['file with space.txt'],
			['spéciäl fäile'],
			['test single\'quote.txt'],
		];
	}

	public static function directoryProvider(): array {
		return [
			['folder'],
			[' folder'],
			['folder '],
			['folder with space'],
			['spéciäl földer'],
			['test single\'quote'],
		];
	}

	public static function loremFileProvider(): array {
		$root = \OC::$SERVERROOT . '/tests/data/';
		return [
			// small file
			[$root . 'lorem.txt'],
			// bigger file (> 8 KB which is the standard PHP block size)
			[$root . 'lorem-big.txt']
		];
	}

	/**
	 * test the various uses of file_get_contents and file_put_contents
	 *
	 * @dataProvider loremFileProvider
	 */
	public function testGetPutContents($sourceFile): void {
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
	public function testMimeType(): void {
		$this->assertEquals('httpd/unix-directory', $this->instance->getMimeType('/'));
		$this->assertEquals(false, $this->instance->getMimeType('/non/existing/file'));

		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->instance->file_put_contents('/lorem.txt', file_get_contents($textFile, 'r'));
		$this->assertEquals('text/plain', $this->instance->getMimeType('/lorem.txt'));

		$pngFile = \OC::$SERVERROOT . '/tests/data/desktopapp.png';
		$this->instance->file_put_contents('/desktopapp.png', file_get_contents($pngFile, 'r'));
		$this->assertEquals('image/png', $this->instance->getMimeType('/desktopapp.png'));

		$svgFile = \OC::$SERVERROOT . '/tests/data/desktopapp.svg';
		$this->instance->file_put_contents('/desktopapp.svg', file_get_contents($svgFile, 'r'));
		$this->assertEquals('image/svg+xml', $this->instance->getMimeType('/desktopapp.svg'));
	}


	public static function copyAndMoveProvider(): array {
		return [
			['/source.txt', '/target.txt'],
			['/source.txt', '/target with space.txt'],
			['/source with space.txt', '/target.txt'],
			['/source with space.txt', '/target with space.txt'],
			['/source.txt', '/tärgét.txt'],
			['/sòurcē.txt', '/target.txt'],
			['/sòurcē.txt', '/tärgét.txt'],
			['/single \' quote.txt', '/tar\'get.txt'],
		];
	}

	public function initSourceAndTarget($source, $target = null) {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->instance->file_put_contents($source, file_get_contents($textFile));
		if ($target) {
			$testContents = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$this->instance->file_put_contents($target, $testContents);
		}
	}

	public function assertSameAsLorem($file) {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->assertEquals(
			file_get_contents($textFile),
			$this->instance->file_get_contents($file),
			'Expected ' . $file . ' to be a copy of ' . $textFile
		);
	}

	/**
	 * @dataProvider copyAndMoveProvider
	 */
	public function testCopy($source, $target): void {
		$this->initSourceAndTarget($source);

		$this->instance->copy($source, $target);

		$this->assertTrue($this->instance->file_exists($target), $target . ' was not created');
		$this->assertSameAsLorem($target);
		$this->assertTrue($this->instance->file_exists($source), $source . ' was deleted');
	}

	/**
	 * @dataProvider copyAndMoveProvider
	 */
	public function testMove($source, $target): void {
		$this->initSourceAndTarget($source);

		$this->instance->rename($source, $target);

		$this->wait();
		$this->assertTrue($this->instance->file_exists($target), $target . ' was not created');
		$this->assertFalse($this->instance->file_exists($source), $source . ' still exists');
		$this->assertSameAsLorem($target);
	}

	/**
	 * @dataProvider copyAndMoveProvider
	 */
	public function testCopyOverwrite($source, $target): void {
		$this->initSourceAndTarget($source, $target);

		$this->instance->copy($source, $target);

		$this->assertTrue($this->instance->file_exists($target), $target . ' was not created');
		$this->assertTrue($this->instance->file_exists($source), $source . ' was deleted');
		$this->assertSameAsLorem($target);
		$this->assertSameAsLorem($source);
	}

	/**
	 * @dataProvider copyAndMoveProvider
	 */
	public function testMoveOverwrite($source, $target): void {
		$this->initSourceAndTarget($source, $target);

		$this->instance->rename($source, $target);

		$this->assertTrue($this->instance->file_exists($target), $target . ' was not created');
		$this->assertFalse($this->instance->file_exists($source), $source . ' still exists');
		$this->assertSameAsLorem($target);
	}

	public function testLocal(): void {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->instance->file_put_contents('/lorem.txt', file_get_contents($textFile));
		$localFile = $this->instance->getLocalFile('/lorem.txt');
		$this->assertTrue(file_exists($localFile));
		$this->assertEquals(file_get_contents($textFile), file_get_contents($localFile));

		$this->instance->mkdir('/folder');
		$this->instance->file_put_contents('/folder/lorem.txt', file_get_contents($textFile));
		$this->instance->file_put_contents('/folder/bar.txt', 'asd');
		$this->instance->mkdir('/folder/recursive');
		$this->instance->file_put_contents('/folder/recursive/file.txt', 'foo');

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

	public function testStat(): void {
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

	/**
	 * Test whether checkUpdate properly returns false when there was
	 * no change.
	 */
	public function testCheckUpdate(): void {
		if ($this->instance instanceof Wrapper) {
			$this->markTestSkipped('Cannot test update check on wrappers');
		}
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$watcher = $this->instance->getWatcher();
		$watcher->setPolicy(Watcher::CHECK_ALWAYS);
		$this->instance->file_put_contents('/lorem.txt', file_get_contents($textFile));
		$this->assertTrue($watcher->checkUpdate('/lorem.txt'), 'Update detected');
		$this->assertFalse($watcher->checkUpdate('/lorem.txt'), 'No update');
	}

	public function testUnlink(): void {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->instance->file_put_contents('/lorem.txt', file_get_contents($textFile));

		$this->assertTrue($this->instance->file_exists('/lorem.txt'));

		$this->assertTrue($this->instance->unlink('/lorem.txt'));
		$this->wait();

		$this->assertFalse($this->instance->file_exists('/lorem.txt'));
	}

	/**
	 * @dataProvider fileNameProvider
	 */
	public function testFOpen($fileName): void {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';

		$fh = @$this->instance->fopen($fileName, 'r');
		if ($fh) {
			fclose($fh);
		}
		$this->assertFalse($fh);
		$this->assertFalse($this->instance->file_exists($fileName));

		$fh = $this->instance->fopen($fileName, 'w');
		fwrite($fh, file_get_contents($textFile));
		fclose($fh);
		$this->assertTrue($this->instance->file_exists($fileName));

		$fh = $this->instance->fopen($fileName, 'r');
		$content = stream_get_contents($fh);
		$this->assertEquals(file_get_contents($textFile), $content);
	}

	public function testTouchCreateFile(): void {
		$this->assertFalse($this->instance->file_exists('touch'));
		// returns true on success
		$this->assertTrue($this->instance->touch('touch'));
		$this->assertTrue($this->instance->file_exists('touch'));
	}

	public function testRecursiveRmdir(): void {
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

	public function testRmdirEmptyFolder(): void {
		$this->assertTrue($this->instance->mkdir('empty'));
		$this->wait();
		$this->assertTrue($this->instance->rmdir('empty'));
		$this->assertFalse($this->instance->file_exists('empty'));
	}

	public function testRecursiveUnlink(): void {
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

	public static function hashProvider(): array {
		return [
			['Foobar', 'md5'],
			['Foobar', 'sha1'],
			['Foobar', 'sha256'],
		];
	}

	/**
	 * @dataProvider hashProvider
	 */
	public function testHash($data, $type): void {
		$this->instance->file_put_contents('hash.txt', $data);
		$this->assertEquals(hash($type, $data), $this->instance->hash($type, 'hash.txt'));
		$this->assertEquals(hash($type, $data, true), $this->instance->hash($type, 'hash.txt', true));
	}

	public function testHashInFileName(): void {
		$this->instance->file_put_contents('#test.txt', 'data');
		$this->assertEquals('data', $this->instance->file_get_contents('#test.txt'));

		$this->instance->mkdir('#foo');
		$this->instance->file_put_contents('#foo/test.txt', 'data');
		$this->assertEquals('data', $this->instance->file_get_contents('#foo/test.txt'));

		$dh = $this->instance->opendir('#foo');
		$content = [];
		while ($file = readdir($dh)) {
			if ($file != '.' and $file != '..') {
				$content[] = $file;
			}
		}

		$this->assertEquals(['test.txt'], $content);
	}

	public function testCopyOverWriteFile(): void {
		$this->instance->file_put_contents('target.txt', 'foo');
		$this->instance->file_put_contents('source.txt', 'bar');
		$this->instance->copy('source.txt', 'target.txt');
		$this->assertEquals('bar', $this->instance->file_get_contents('target.txt'));
	}

	public function testRenameOverWriteFile(): void {
		$this->instance->file_put_contents('target.txt', 'foo');
		$this->instance->file_put_contents('source.txt', 'bar');
		$this->instance->rename('source.txt', 'target.txt');
		$this->assertEquals('bar', $this->instance->file_get_contents('target.txt'));
		$this->assertFalse($this->instance->file_exists('source.txt'));
	}

	public function testRenameDirectory(): void {
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

		$contents = iterator_to_array($this->instance->getDirectoryContent(''));
		$this->assertCount(1, $contents);

		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
		$this->assertEquals('qwerty', $this->instance->file_get_contents('target/test2.txt'));
		$this->assertEquals('bar', $this->instance->file_get_contents('target/subfolder/test.txt'));
	}

	public function testRenameOverWriteDirectory(): void {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');

		$this->instance->mkdir('target');
		$this->instance->file_put_contents('target/test1.txt', 'bar');
		$this->instance->file_put_contents('target/test2.txt', 'bar');

		$this->assertTrue($this->instance->rename('source', 'target'), 'rename must return true on success');

		$this->assertFalse($this->instance->file_exists('source'), 'source has not been removed');
		$this->assertFalse($this->instance->file_exists('source/test1.txt'), 'source/test1.txt has not been removed');
		$this->assertFalse($this->instance->file_exists('target/test2.txt'), 'target/test2.txt has not been removed');
		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'), 'target/test1.txt has not been overwritten');
	}

	public function testRenameOverWriteDirectoryOverFile(): void {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');

		$this->instance->file_put_contents('target', 'bar');

		$this->assertTrue($this->instance->rename('source', 'target'), 'rename must return true on success');

		$this->assertFalse($this->instance->file_exists('source'));
		$this->assertFalse($this->instance->file_exists('source/test1.txt'));
		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
	}

	public function testCopyDirectory(): void {
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

	public function testCopyOverWriteDirectory(): void {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');

		$this->instance->mkdir('target');
		$this->instance->file_put_contents('target/test1.txt', 'bar');
		$this->instance->file_put_contents('target/test2.txt', 'bar');

		$this->instance->copy('source', 'target');

		$this->assertFalse($this->instance->file_exists('target/test2.txt'), 'File target/test2.txt should no longer exist, but does');
		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
	}

	public function testCopyOverWriteDirectoryOverFile(): void {
		$this->instance->mkdir('source');
		$this->instance->file_put_contents('source/test1.txt', 'foo');

		$this->instance->file_put_contents('target', 'bar');

		$this->instance->copy('source', 'target');

		$this->assertEquals('foo', $this->instance->file_get_contents('target/test1.txt'));
	}

	public function testInstanceOfStorage(): void {
		$this->assertTrue($this->instance->instanceOfStorage(IStorage::class));
		$this->assertTrue($this->instance->instanceOfStorage(get_class($this->instance)));
		$this->assertFalse($this->instance->instanceOfStorage('\OC'));
	}

	/**
	 * @dataProvider copyAndMoveProvider
	 */
	public function testCopyFromSameStorage($source, $target): void {
		$this->initSourceAndTarget($source);

		$this->instance->copyFromStorage($this->instance, $source, $target);

		$this->assertTrue($this->instance->file_exists($target), $target . ' was not created');
		$this->assertSameAsLorem($target);
		$this->assertTrue($this->instance->file_exists($source), $source . ' was deleted');
	}

	public function testIsCreatable(): void {
		$this->instance->mkdir('source');
		$this->assertTrue($this->instance->isCreatable('source'));
	}

	public function testIsReadable(): void {
		$this->instance->mkdir('source');
		$this->assertTrue($this->instance->isReadable('source'));
	}

	public function testIsUpdatable(): void {
		$this->instance->mkdir('source');
		$this->assertTrue($this->instance->isUpdatable('source'));
	}

	public function testIsDeletable(): void {
		$this->instance->mkdir('source');
		$this->assertTrue($this->instance->isDeletable('source'));
	}

	public function testIsShareable(): void {
		$this->instance->mkdir('source');
		$this->assertTrue($this->instance->isSharable('source'));
	}

	public function testStatAfterWrite(): void {
		$this->instance->file_put_contents('foo.txt', 'bar');
		$stat = $this->instance->stat('foo.txt');
		$this->assertEquals(3, $stat['size']);

		$fh = $this->instance->fopen('foo.txt', 'w');
		fwrite($fh, 'qwerty');
		fclose($fh);

		$stat = $this->instance->stat('foo.txt');
		$this->assertEquals(6, $stat['size']);
	}

	public function testPartFile(): void {
		$this->instance->file_put_contents('bar.txt.part', 'bar');
		$this->instance->rename('bar.txt.part', 'bar.txt');
		$this->assertEquals('bar', $this->instance->file_get_contents('bar.txt'));
	}

	public function testWriteStream(): void {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';

		if (!$this->instance->instanceOfStorage(IWriteStreamStorage::class)) {
			$this->markTestSkipped('Not a WriteSteamStorage');
		}
		/** @var IWriteStreamStorage $storage */
		$storage = $this->instance;

		$source = fopen($textFile, 'r');

		$storage->writeStream('test.txt', $source);
		$this->assertTrue($storage->file_exists('test.txt'));
		$this->assertStringEqualsFile($textFile, $storage->file_get_contents('test.txt'));
		$this->assertEquals('resource (closed)', gettype($source));
	}

	public function testFseekSize(): void {
		$textFile = \OC::$SERVERROOT . '/tests/data/lorem.txt';
		$this->instance->file_put_contents('bar.txt', file_get_contents($textFile));

		$size = $this->instance->filesize('bar.txt');
		$this->assertEquals(filesize($textFile), $size);
		$fh = $this->instance->fopen('bar.txt', 'r');

		fseek($fh, 0, SEEK_END);
		$pos = ftell($fh);

		$this->assertEquals($size, $pos);
	}
}

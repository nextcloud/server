<?php

namespace Test\Files\Stream;

use OC\Files\View;
use OCA\Encryption_Dummy\DummyModule;

class Encryption extends \Test\TestCase {

	/**
	 * @param string $fileName
	 * @param string $mode
	 * @param integer $unencryptedSize
	 * @return resource
	 */
	protected function getStream($fileName, $mode, $unencryptedSize) {

		$size = filesize($fileName);
		$source = fopen($fileName, $mode);
		$internalPath = $fileName;
		$fullPath = $fileName;
		$header = [];
		$uid = '';
		$encryptionModule = $this->buildMockModule();
		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()->getMock();
		$encStorage = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Encryption')
			->disableOriginalConstructor()->getMock();
		$config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$groupManager = $this->getMockBuilder('\OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();
		$file = $this->getMockBuilder('\OC\Encryption\File')
			->disableOriginalConstructor()
			->setMethods(['getAccessList'])
			->getMock();
		$file->expects($this->any())->method('getAccessList')->willReturn([]);
		$util = $this->getMock('\OC\Encryption\Util', ['getUidAndFilename'], [new View(), new \OC\User\Manager(), $groupManager, $config]);
		$util->expects($this->any())
			->method('getUidAndFilename')
			->willReturn(['user1', $internalPath]);

		return \OC\Files\Stream\Encryption::wrap($source, $internalPath,
			$fullPath, $header, $uid, $encryptionModule, $storage, $encStorage,
			$util, $file, $mode, $size, $unencryptedSize);
	}

	/**
	 * @dataProvider dataProviderStreamOpen()
	 */
	public function testStreamOpen($mode,
								   $fullPath,
								   $fileExists,
								   $expectedSharePath,
								   $expectedSize,
								   $expectedReadOnly) {

		// build mocks
		$encryptionModuleMock = $this->getMockBuilder('\OCP\Encryption\IEncryptionModule')
		->disableOriginalConstructor()->getMock();
		$encryptionModuleMock->expects($this->once())
			->method('getUnencryptedBlockSize')->willReturn(99);
		$encryptionModuleMock->expects($this->once())
			->method('begin')->willReturn(true);

		$storageMock = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()->getMock();
		$storageMock->expects($this->once())->method('file_exists')->willReturn($fileExists);

		$fileMock = $this->getMockBuilder('\OC\Encryption\File')
			->disableOriginalConstructor()->getMock();
		$fileMock->expects($this->once())->method('getAccessList')
			->will($this->returnCallback(function($sharePath) use ($expectedSharePath) {
				$this->assertSame($expectedSharePath, $sharePath);
				return array();
			}));

		// get a instance of the stream wrapper
		$streamWrapper = $this->getMockBuilder('\OC\Files\Stream\Encryption')
			->setMethods(['loadContext'])->disableOriginalConstructor()->getMock();

		// set internal properties of the stream wrapper
		$stream = new \ReflectionClass('\OC\Files\Stream\Encryption');
		$encryptionModule = $stream->getProperty('encryptionModule');
		$encryptionModule->setAccessible(true);
		$encryptionModule->setValue($streamWrapper, $encryptionModuleMock);
		$encryptionModule->setAccessible(false);
		$storage = $stream->getProperty('storage');
		$storage->setAccessible(true);
		$storage->setValue($streamWrapper, $storageMock);
		$storage->setAccessible(false);
		$file = $stream->getProperty('file');
		$file->setAccessible(true);
		$file->setValue($streamWrapper, $fileMock);
		$file->setAccessible(false);
		$fullPathP = $stream->getProperty('fullPath');
		$fullPathP->setAccessible(true);
		$fullPathP->setValue($streamWrapper, $fullPath);
		$fullPathP->setAccessible(false);
		$header = $stream->getProperty('header');
		$header->setAccessible(true);
		$header->setValue($streamWrapper, array());
		$header->setAccessible(false);

		// call stream_open, that's the method we want to test
		$dummyVar = 'foo';
		$streamWrapper->stream_open('', $mode, '', $dummyVar);

		// check internal properties
		$size = $stream->getProperty('size');
		$size->setAccessible(true);
		$this->assertSame($expectedSize,
			$size->getValue($streamWrapper)
		);
		$size->setAccessible(false);

		$unencryptedSize = $stream->getProperty('unencryptedSize');
		$unencryptedSize->setAccessible(true);
		$this->assertSame($expectedSize,
			$unencryptedSize->getValue($streamWrapper)
		);
		$unencryptedSize->setAccessible(false);

		$readOnly = $stream->getProperty('readOnly');
		$readOnly->setAccessible(true);
		$this->assertSame($expectedReadOnly,
			$readOnly->getValue($streamWrapper)
		);
		$readOnly->setAccessible(false);
	}

	public function dataProviderStreamOpen() {
		return array(
			array('r', '/foo/bar/test.txt', true, '/foo/bar/test.txt', null, true),
			array('r', '/foo/bar/test.txt', false, '/foo/bar', null, true),
			array('w', '/foo/bar/test.txt', true, '/foo/bar/test.txt', 0, false),
		);
	}

	public function testWriteRead() {
		$fileName = tempnam("/tmp", "FOO");
		$stream = $this->getStream($fileName, 'w+', 0);
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		fclose($stream);

		$stream = $this->getStream($fileName, 'r', 6);
		$this->assertEquals('foobar', fread($stream, 100));
		fclose($stream);
	}

	public function testSeek() {
		$fileName = tempnam("/tmp", "FOO");
		$stream = $this->getStream($fileName, 'w+', 0);
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		$this->assertEquals(0, fseek($stream, 3));
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		fclose($stream);

		$stream = $this->getStream($fileName, 'r', 9);
		$this->assertEquals('foofoobar', fread($stream, 100));
		fclose($stream);
	}

	public function testWriteReadBigFile() {
		$expectedData = file_get_contents(\OC::$SERVERROOT . '/tests/data/lorem-big.txt');
		// write it
		$fileName = tempnam("/tmp", "FOO");
		$stream = $this->getStream($fileName, 'w+', 0);
		fwrite($stream, $expectedData);
		fclose($stream);

		// read it all
		$stream = $this->getStream($fileName, 'r', strlen($expectedData));
		$data = stream_get_contents($stream);
		fclose($stream);

		$this->assertEquals($expectedData, $data);
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildMockModule() {
		$encryptionModule = $this->getMockBuilder('\OCP\Encryption\IEncryptionModule')
			->disableOriginalConstructor()
			->setMethods(['getId', 'getDisplayName', 'begin', 'end', 'encrypt', 'decrypt', 'update', 'shouldEncrypt', 'getUnencryptedBlockSize'])
			->getMock();

		$encryptionModule->expects($this->any())->method('getId')->willReturn('UNIT_TEST_MODULE');
		$encryptionModule->expects($this->any())->method('getDisplayName')->willReturn('Unit test module');
		$encryptionModule->expects($this->any())->method('begin')->willReturn([]);
		$encryptionModule->expects($this->any())->method('end')->willReturn('');
		$encryptionModule->expects($this->any())->method('encrypt')->willReturnArgument(0);
		$encryptionModule->expects($this->any())->method('decrypt')->willReturnArgument(0);
		$encryptionModule->expects($this->any())->method('update')->willReturn(true);
		$encryptionModule->expects($this->any())->method('shouldEncrypt')->willReturn(true);
		$encryptionModule->expects($this->any())->method('getUnencryptedBlockSize')->willReturn(8192);
		return $encryptionModule;
	}
}

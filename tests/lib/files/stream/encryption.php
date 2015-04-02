<?php

namespace Test\Files\Stream;

use OC\Files\View;
use OCA\Encryption_Dummy\DummyModule;

class Encryption extends \Test\TestCase {

	/**
	 * @param string $mode
	 * @param integer $limit
	 */
	protected function getStream($fileName, $mode) {

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
		$file = $this->getMockBuilder('\OC\Encryption\File')
			->disableOriginalConstructor()
			->getMock();
		$util = $this->getMock('\OC\Encryption\Util', ['getUidAndFilename'], [new View(), new \OC\User\Manager(), $config]);
		$util->expects($this->any())
			->method('getUidAndFilename')
			->willReturn(['user1', $internalPath]);
		$size = 12;
		$unencryptedSize = 8000;

		return \OC\Files\Stream\Encryption::wrap($source, $internalPath,
			$fullPath, $header, $uid, $encryptionModule, $storage, $encStorage,
			$util, $file, $mode, $size, $unencryptedSize);
	}

	public function testWriteRead() {
		$fileName = tempnam("/tmp", "FOO");
		$stream = $this->getStream($fileName, 'w+');
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		fclose($stream);

		$stream = $this->getStream($fileName, 'r');
		$this->assertEquals('foobar', fread($stream, 100));
		fclose($stream);
	}

	public function testSeek() {
		$fileName = tempnam("/tmp", "FOO");
		$stream = $this->getStream($fileName, 'w+');
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		$this->assertEquals(0, fseek($stream, 3));
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		fclose($stream);

		$stream = $this->getStream($fileName, 'r');
		$this->assertEquals('foofoobar', fread($stream, 100));
		fclose($stream);
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildMockModule() {
		$encryptionModule = $this->getMockBuilder('\OCP\Encryption\IEncryptionModule')
			->disableOriginalConstructor()
			->setMethods(['getId', 'getDisplayName', 'begin', 'end', 'encrypt', 'decrypt', 'update', 'shouldEncrypt', 'calculateUnencryptedSize', 'getUnencryptedBlockSize'])
			->getMock();

		$encryptionModule->expects($this->any())->method('getId')->willReturn('UNIT_TEST_MODULE');
		$encryptionModule->expects($this->any())->method('getDisplayName')->willReturn('Unit test module');
		$encryptionModule->expects($this->any())->method('begin')->willReturn([]);
		$encryptionModule->expects($this->any())->method('end')->willReturn('');
		$encryptionModule->expects($this->any())->method('encrypt')->willReturnArgument(0);
		$encryptionModule->expects($this->any())->method('decrypt')->willReturnArgument(0);
		$encryptionModule->expects($this->any())->method('update')->willReturn(true);
		$encryptionModule->expects($this->any())->method('shouldEncrypt')->willReturn(true);
		$encryptionModule->expects($this->any())->method('calculateUnencryptedSize')->willReturn(42);
		$encryptionModule->expects($this->any())->method('getUnencryptedBlockSize')->willReturn(6126);
		return $encryptionModule;
	}
}

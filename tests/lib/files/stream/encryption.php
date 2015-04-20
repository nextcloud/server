<?php

namespace Test\Files\Stream;

use OC\Files\View;
use OCA\Encryption_Dummy\DummyModule;

class Encryption extends \Test\TestCase {

	/**
	 * @param string $mode
	 * @param integer $limit
	 */
	protected function getStream($fileName, $mode, $unencryptedSize) {
		clearstatcache();
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

	function dataFilesProvider() {
		return [
			['lorem-big.txt'],
			['block-aligned.txt'],
			['block-aligned-plus-one.txt'],
		];
	}

	/**
	 * @dataProvider dataFilesProvider
	 */
	public function testWriteReadBigFile($testFile) {
		$expectedData = file_get_contents(\OC::$SERVERROOT . '/tests/data/' . $testFile);
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

		// another read test with a loop like we do in several places:
		$stream = $this->getStream($fileName, 'r', strlen($expectedData));
		$data = '';
		while (!feof($stream)) {
			$data .= fread($stream, 8192);
		}
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
		$encryptionModule->expects($this->any())->method('encrypt')->willReturnCallback(function($data) {
			// simulate different block size by adding some padding to the data
			if (isset($data[6125])) {
				return str_pad($data, 8192, 'X');
			}
			// last block
			return $data;
		});
		$encryptionModule->expects($this->any())->method('decrypt')->willReturnCallback(function($data) {
			if (isset($data[8191])) {
				return substr($data, 0, 6126);
			}
			// last block
			return $data;
		});
		$encryptionModule->expects($this->any())->method('update')->willReturn(true);
		$encryptionModule->expects($this->any())->method('shouldEncrypt')->willReturn(true);
		$encryptionModule->expects($this->any())->method('getUnencryptedBlockSize')->willReturn(6126);
		return $encryptionModule;
	}
}

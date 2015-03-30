<?php

namespace Test\Files\Stream;

use OC\Files\View;
use OCA\Encryption_Dummy\DummyModule;

class Encryption extends \Test\TestCase {

	/**
	 * @param string $mode
	 * @param integer $limit
	 */
	protected function getStream($mode) {

		$source = fopen('php://temp', $mode);
		$internalPath = '';
		$fullPath = '';
		$header = [];
		$uid = '';
		$encryptionModule = new DummyModule();
		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()->getMock();
		$encStorage = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Encryption')
			->disableOriginalConstructor()->getMock();
		$config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$util = new \OC\Encryption\Util(new View(), new \OC\User\Manager(), $config);
		$size = 12;
		$unencryptedSize = 8000;

		return \OC\Files\Stream\Encryption::wrap($source, $internalPath,
			$fullPath, $header, $uid, $encryptionModule, $storage, $encStorage,
			$util, $mode, $size, $unencryptedSize);
	}

	public function testWriteEnoughSpace() {
		$stream = $this->getStream('w+');
		$this->assertEquals(6, fwrite($stream, 'foobar'));
		rewind($stream);
		$this->assertEquals('foobar', fread($stream, 100));
	}
}

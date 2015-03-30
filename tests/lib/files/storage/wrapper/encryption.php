<?php

namespace Test\Files\Storage\Wrapper;

use OC\Files\View;
use OCA\Encryption_Dummy\DummyModule;

class Encryption extends \Test\Files\Storage\Storage {

	/**
	 * @var \OC\Files\Storage\Temporary
	 */
	private $sourceStorage;

	public function setUp() {

		parent::setUp();

		$encryptionManager = $this->getMockBuilder('\OC\Encryption\Manager')
			->disableOriginalConstructor()
			->setMethods(['getDefaultEncryptionModule', 'getEncryptionModule'])
			->getMock();
		$encryptionManager->expects($this->any())
			->method('getDefaultEncryptionModule')
			->willReturn(new DummyModule());

		$util = new \OC\Encryption\Util(new View(), new \OC\User\Manager());

		$logger = $this->getMock('\OC\Log');

		$this->sourceStorage = new \OC\Files\Storage\Temporary(array());
		$this->instance = new \OC\Files\Storage\Wrapper\Encryption([
			'storage' => $this->sourceStorage,
			'root' => 'foo',
			'mountPoint' => '/'
		],
			$encryptionManager, $util, $logger
		);
	}

//	public function testMkDirRooted() {
//		$this->instance->mkdir('bar');
//		$this->assertTrue($this->sourceStorage->is_dir('foo/bar'));
//	}
//
//	public function testFilePutContentsRooted() {
//		$this->instance->file_put_contents('bar', 'asd');
//		$this->assertEquals('asd', $this->sourceStorage->file_get_contents('foo/bar'));
//	}
}

<?php

namespace Test\Files\Storage\Wrapper;

use OC\Files\Storage\Temporary;
use OC\Files\View;

class Encryption extends \Test\Files\Storage\Storage {

	/**
	 * @var Temporary
	 */
	private $sourceStorage;

	/**
	 * @var \OC\Encryption\Keys\Storage | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $keyStore;

	public function setUp() {

		parent::setUp();

		$mockModule = $this->buildMockModule();
		$encryptionManager = $this->getMockBuilder('\OC\Encryption\Manager')
			->disableOriginalConstructor()
			->setMethods(['getDefaultEncryptionModule', 'getEncryptionModule', 'isEnabled'])
			->getMock();
		$encryptionManager->expects($this->any())
			->method('getDefaultEncryptionModule')
			->willReturn($mockModule);
		$encryptionManager->expects($this->any())
			->method('getEncryptionModule')
			->willReturn($mockModule);
		$encryptionManager->expects($this->any())
			->method('isEnabled')
			->willReturn(true);

		$config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$groupManager = $this->getMockBuilder('\OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();

		$util = $this->getMock('\OC\Encryption\Util', ['getUidAndFilename'], [new View(), new \OC\User\Manager(), $groupManager, $config]);
		$util->expects($this->any())
			->method('getUidAndFilename')
			->willReturnCallback(function ($path) {
				return ['user1', $path];
			});

		$file = $this->getMockBuilder('\OC\Encryption\File')
			->disableOriginalConstructor()
			->setMethods(['getAccessList'])
			->getMock();
		$file->expects($this->any())->method('getAccessList')->willReturn([]);

		$logger = $this->getMock('\OC\Log');

		$this->sourceStorage = new Temporary(array());
		$this->keyStore = $this->getMockBuilder('\OC\Encryption\Keys\Storage')
			->disableOriginalConstructor()->getMock();
		$mount = $this->getMockBuilder('\OC\Files\Mount\MountPoint')
			->disableOriginalConstructor()
			->setMethods(['getOption'])
			->getMock();
		$mount->expects($this->any())->method('getOption')->willReturn(true);
		$this->instance = new \OC\Files\Storage\Wrapper\Encryption([
			'storage' => $this->sourceStorage,
			'root' => 'foo',
			'mountPoint' => '/',
			'mount' => $mount
		],
			$encryptionManager, $util, $logger, $file, null, $this->keyStore
		);
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

	public function testRename() {
		$this->keyStore
			->expects($this->once())
			->method('renameKeys');
		$this->instance->mkdir('folder');
		$this->instance->rename('folder', 'flodder');
	}
}

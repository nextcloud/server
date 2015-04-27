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
	 * @var \OC\Files\Storage\Wrapper\Encryption
	 */
	protected $instance;

	/**
	 * @var \OC\Encryption\Keys\Storage | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $keyStore;

	/**
	 * @var \OC\Encryption\Util | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $util;


	/**
	 * @var \OC\Encryption\Manager | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $encryptionManager;

	/**
	 * @var \OCP\Encryption\IEncryptionModule | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $encryptionModule;


	/**
	 * @var \OC\Encryption\Update | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $update;

	protected function setUp() {

		parent::setUp();

		$mockModule = $this->buildMockModule();
		$this->encryptionManager = $this->getMockBuilder('\OC\Encryption\Manager')
			->disableOriginalConstructor()
			->setMethods(['getEncryptionModule', 'isEnabled'])
			->getMock();
		$this->encryptionManager->expects($this->any())
			->method('getEncryptionModule')
			->willReturn($mockModule);
		$this->encryptionManager->expects($this->any())
			->method('isEnabled')
			->willReturn(true);

		$config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$groupManager = $this->getMockBuilder('\OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();

		$this->util = $this->getMock('\OC\Encryption\Util', ['getUidAndFilename', 'isFile'], [new View(), new \OC\User\Manager(), $groupManager, $config]);
		$this->util->expects($this->any())
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
		$this->update = $this->getMockBuilder('\OC\Encryption\Update')
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
			$this->encryptionManager, $this->util, $logger, $file, null, $this->keyStore, $this->update
		);
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildMockModule() {
		$this->encryptionModule = $this->getMockBuilder('\OCP\Encryption\IEncryptionModule')
			->disableOriginalConstructor()
			->setMethods(['getId', 'getDisplayName', 'begin', 'end', 'encrypt', 'decrypt', 'update', 'shouldEncrypt', 'getUnencryptedBlockSize'])
			->getMock();

		$this->encryptionModule->expects($this->any())->method('getId')->willReturn('UNIT_TEST_MODULE');
		$this->encryptionModule->expects($this->any())->method('getDisplayName')->willReturn('Unit test module');
		$this->encryptionModule->expects($this->any())->method('begin')->willReturn([]);
		$this->encryptionModule->expects($this->any())->method('end')->willReturn('');
		$this->encryptionModule->expects($this->any())->method('encrypt')->willReturnArgument(0);
		$this->encryptionModule->expects($this->any())->method('decrypt')->willReturnArgument(0);
		$this->encryptionModule->expects($this->any())->method('update')->willReturn(true);
		$this->encryptionModule->expects($this->any())->method('shouldEncrypt')->willReturn(true);
		$this->encryptionModule->expects($this->any())->method('getUnencryptedBlockSize')->willReturn(8192);
		return $this->encryptionModule;
	}

	/**
	 * @dataProvider dataTestCopyAndRename
	 *
	 * @param string $source
	 * @param string $target
	 * @param boolean $renameKeysReturn
	 * @param boolean $shouldUpdate
	 */
	public function testRename($source, $target, $renameKeysReturn, $shouldUpdate) {
		$this->keyStore
			->expects($this->once())
			->method('renameKeys')
			->willReturn($renameKeysReturn);
		$this->util->expects($this->any())
			->method('isFile')->willReturn(true);
		if ($shouldUpdate) {
			$this->update->expects($this->once())
				->method('update');
		} else {
			$this->update->expects($this->never())
				->method('update');
		}

		$this->instance->mkdir($source);
		$this->instance->mkdir(dirname($target));
		$this->instance->rename($source, $target);
	}

	/**
	 * @dataProvider dataTestCopyAndRename
	 *
	 * @param string $source
	 * @param string $target
	 * @param boolean $copyKeysReturn
	 * @param boolean $shouldUpdate
	 */
	public function testCopyTesting($source, $target, $copyKeysReturn, $shouldUpdate) {
		$this->keyStore
			->expects($this->once())
			->method('copyKeys')
			->willReturn($copyKeysReturn);
		$this->util->expects($this->any())
			->method('isFile')->willReturn(true);
		if ($shouldUpdate) {
			$this->update->expects($this->once())
				->method('update');
		} else {
			$this->update->expects($this->never())
				->method('update');
		}

		$this->instance->mkdir($source);
		$this->instance->mkdir(dirname($target));
		$this->instance->copy($source, $target);
	}

	/**
	 * @dataProvider copyAndMoveProvider
	 */
	public function testCopy($source, $target) {
		$this->assertTrue(true, 'Replaced by testCopyTesting()');
	}

	/**
	 * data provider for testCopyTesting() and dataTestCopyAndRename()
	 *
	 * @return array
	 */
	public function dataTestCopyAndRename() {
		return array(
			array('source', 'target', false, false),
			array('source', 'target', true, false),
			array('source', '/subFolder/target', false, false),
			array('source', '/subFolder/target', true, true),
		);
	}

}

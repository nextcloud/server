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
	 * @var \OC\Files\Storage\Wrapper\Encryption | \PHPUnit_Framework_MockObject_MockObject
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

	/**
	 * @var \OC\Files\Cache\Cache | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $cache;

	/**
	 * @var \OC\Log | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $logger;

	/**
	 * @var \OC\Encryption\File | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $file;


	/**
	 * @var \OC\Files\Mount\MountPoint | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $mount;

	/**
	 * @var \OC\Files\Mount\Manager | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $mountManager;

	/**
	 * @var \OC\Group\Manager | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $groupManager;

	/**
	 * @var \OCP\IConfig | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $config;


	/** @var  integer dummy unencrypted size */
	private $dummySize = -1;

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

		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager = $this->getMockBuilder('\OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();

		$this->util = $this->getMock('\OC\Encryption\Util',
			['getUidAndFilename', 'isFile', 'isExcluded'],
			[new View(), new \OC\User\Manager(), $this->groupManager, $this->config]);
		$this->util->expects($this->any())
			->method('getUidAndFilename')
			->willReturnCallback(function ($path) {
				return ['user1', $path];
			});

		$this->file = $this->getMockBuilder('\OC\Encryption\File')
			->disableOriginalConstructor()
			->setMethods(['getAccessList'])
			->getMock();
		$this->file->expects($this->any())->method('getAccessList')->willReturn([]);

		$this->logger = $this->getMock('\OC\Log');

		$this->sourceStorage = new Temporary(array());

		$this->keyStore = $this->getMockBuilder('\OC\Encryption\Keys\Storage')
			->disableOriginalConstructor()->getMock();

		$this->update = $this->getMockBuilder('\OC\Encryption\Update')
			->disableOriginalConstructor()->getMock();

		$this->mount = $this->getMockBuilder('\OC\Files\Mount\MountPoint')
			->disableOriginalConstructor()
			->setMethods(['getOption'])
			->getMock();
		$this->mount->expects($this->any())->method('getOption')->willReturn(true);

		$this->cache = $this->getMockBuilder('\OC\Files\Cache\Cache')
			->disableOriginalConstructor()->getMock();
		$this->cache->expects($this->any())
			->method('get')
			->willReturnCallback(function($path) {return ['encrypted' => false, 'path' => $path];});

		$this->mountManager = $this->getMockBuilder('\OC\Files\Mount\Manager')
			->disableOriginalConstructor()->getMock();

		$this->instance = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Encryption')
			->setConstructorArgs(
				[
					[
						'storage' => $this->sourceStorage,
						'root' => 'foo',
						'mountPoint' => '/',
						'mount' => $this->mount
					],
					$this->encryptionManager, $this->util, $this->logger, $this->file, null, $this->keyStore, $this->update, $this->mountManager
				]
			)
			->setMethods(['getMetaData', 'getCache', 'getEncryptionModule'])
			->getMock();

		$this->instance->expects($this->any())
			->method('getMetaData')
			->willReturnCallback(function ($path) {
				return ['encrypted' => true, 'size' => $this->dummySize, 'path' => $path];
			});

		$this->instance->expects($this->any())
			->method('getCache')
			->willReturn($this->cache);

		$this->instance->expects($this->any())
			->method('getEncryptionModule')
			->willReturn($mockModule);
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function buildMockModule() {
		$this->encryptionModule = $this->getMockBuilder('\OCP\Encryption\IEncryptionModule')
			->disableOriginalConstructor()
			->setMethods(['getId', 'getDisplayName', 'begin', 'end', 'encrypt', 'decrypt', 'update', 'shouldEncrypt', 'getUnencryptedBlockSize', 'isReadable'])
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
		$this->encryptionModule->expects($this->any())->method('isReadable')->willReturn(true);
		return $this->encryptionModule;
	}

	/**
	 * @dataProvider dataTestCopyAndRename
	 *
	 * @param string $source
	 * @param string $target
	 * @param $encryptionEnabled
	 * @param boolean $renameKeysReturn
	 */
	public function testRename($source,
							   $target,
							   $encryptionEnabled,
							   $renameKeysReturn) {
		if ($encryptionEnabled) {
			$this->keyStore
				->expects($this->once())
				->method('renameKeys')
				->willReturn($renameKeysReturn);
		} else {
			$this->keyStore
				->expects($this->never())->method('renameKeys');
		}
		$this->util->expects($this->any())
			->method('isFile')->willReturn(true);
		$this->encryptionManager->expects($this->once())
			->method('isEnabled')->willReturn($encryptionEnabled);

		$this->instance->mkdir($source);
		$this->instance->mkdir(dirname($target));
		$this->instance->rename($source, $target);
	}

	/**
	 * @dataProvider dataTestCopyAndRename
	 *
	 * @param string $source
	 * @param string $target
	 * @param $encryptionEnabled
	 * @param boolean $copyKeysReturn
	 * @param boolean $shouldUpdate
	 */
	public function testCopyEncryption($source,
							 $target,
							 $encryptionEnabled,
							 $copyKeysReturn,
							 $shouldUpdate) {

		if ($encryptionEnabled) {
			$this->keyStore
				->expects($this->once())
				->method('copyKeys')
				->willReturn($copyKeysReturn);
			$this->cache->expects($this->once())
				->method('put')
				->with($this->anything(), ['encrypted' => true])
				->willReturn(true);
		} else {
			$this->cache->expects($this->never())->method('put');
			$this->keyStore->expects($this->never())->method('copyKeys');
		}
		$this->util->expects($this->any())
			->method('isFile')->willReturn(true);
		$this->util->expects($this->any())
			->method('isExcluded')->willReturn(false);
		$this->encryptionManager->expects($this->once())
			->method('isEnabled')->willReturn($encryptionEnabled);
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

		if ($encryptionEnabled) {
			$this->assertSame($this->dummySize,
				$this->instance->filesize($target)
			);
		}
	}

	/**
	 * data provider for testCopyTesting() and dataTestCopyAndRename()
	 *
	 * @return array
	 */
	public function dataTestCopyAndRename() {
		return array(
			array('source', 'target', true, false, false),
			array('source', 'target', true, true, false),
			array('source', '/subFolder/target', true, false, false),
			array('source', '/subFolder/target', true, true, true),
			array('source', '/subFolder/target', false, true, false),
		);
	}

	public function testIsLocal() {
		$this->encryptionManager->expects($this->once())
			->method('isEnabled')->willReturn(true);
		$this->assertFalse($this->instance->isLocal());
	}

	/**
	 * @dataProvider dataTestRmdir
	 *
	 * @param string $path
	 * @param boolean $rmdirResult
	 * @param boolean $isExcluded
	 * @param boolean $encryptionEnabled
	 */
	public function testRmdir($path, $rmdirResult, $isExcluded, $encryptionEnabled) {
		$sourceStorage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()->getMock();

		$util = $this->getMockBuilder('\OC\Encryption\Util')->disableOriginalConstructor()->getMock();

		$sourceStorage->expects($this->once())->method('rmdir')->willReturn($rmdirResult);
		$util->expects($this->any())->method('isExcluded')-> willReturn($isExcluded);
		$this->encryptionManager->expects($this->any())->method('isEnabled')->willReturn($encryptionEnabled);

		$encryptionStorage = new \OC\Files\Storage\Wrapper\Encryption(
					[
						'storage' => $sourceStorage,
						'root' => 'foo',
						'mountPoint' => '/mountPoint',
						'mount' => $this->mount
					],
					$this->encryptionManager, $util, $this->logger, $this->file, null, $this->keyStore, $this->update
		);


		if ($rmdirResult === true && $isExcluded === false && $encryptionEnabled === true) {
			$this->keyStore->expects($this->once())->method('deleteAllFileKeys')->with('/mountPoint' . $path);
		} else {
			$this->keyStore->expects($this->never())->method('deleteAllFileKeys');
		}

		$encryptionStorage->rmdir($path);
	}

	public function dataTestRmdir() {
		return array(
			array('/file.txt', true, true, true),
			array('/file.txt', false, true, true),
			array('/file.txt', true, false, true),
			array('/file.txt', false, false, true),
			array('/file.txt', true, true, false),
			array('/file.txt', false, true, false),
			array('/file.txt', true, false, false),
			array('/file.txt', false, false, false),
		);
	}

	/**
	 * @dataProvider dataTestCopyKeys
	 *
	 * @param boolean $excluded
	 * @param boolean $expected
	 */
	public function testCopyKeys($excluded, $expected) {
		$this->util->expects($this->once())
			->method('isExcluded')
			->willReturn($excluded);

		if ($excluded) {
			$this->keyStore->expects($this->never())->method('copyKeys');
		} else {
			$this->keyStore->expects($this->once())->method('copyKeys')->willReturn(true);
		}

		$this->assertSame($expected,
			self::invokePrivate($this->instance, 'copyKeys', ['/source', '/target'])
		);
	}

	public function dataTestCopyKeys() {
		return array(
			array(true, false),
			array(false, true),
		);
	}

	/**
	 * @dataProvider dataTestGetHeader
	 *
	 * @param string $path
	 * @param bool $strippedPathExists
	 * @param string $strippedPath
	 */
	public function testGetHeader($path, $strippedPathExists, $strippedPath) {

		$sourceStorage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()->getMock();

		$util = $this->getMockBuilder('\OC\Encryption\Util')
			->setConstructorArgs([new View(), new \OC\User\Manager(), $this->groupManager, $this->config])
			->getMock();

		$instance = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Encryption')
			->setConstructorArgs(
				[
					[
						'storage' => $sourceStorage,
						'root' => 'foo',
						'mountPoint' => '/',
						'mount' => $this->mount
					],
					$this->encryptionManager, $util, $this->logger, $this->file, null, $this->keyStore, $this->update, $this->mountManager
				]
			)
			->getMock();

		$util->expects($this->once())->method('stripPartialFileExtension')
			->with($path)->willReturn($strippedPath);
		$sourceStorage->expects($this->at(0))
			->method('file_exists')
			->with($strippedPath)
			->willReturn($strippedPathExists);
		$sourceStorage->expects($this->at(1))
			->method('file_exists')
			->with($strippedPathExists ? $strippedPath : $path)
			->willReturn(false);

		$this->invokePrivate($instance, 'getHeader', [$path]);
	}

	public function dataTestGetHeader() {
		return array(
			array('/foo/bar.txt', false, '/foo/bar.txt'),
			array('/foo/bar.txt.part', false, '/foo/bar.txt'),
			array('/foo/bar.txt.ocTransferId7437493.part', false, '/foo/bar.txt'),
			array('/foo/bar.txt.part', true, '/foo/bar.txt'),
			array('/foo/bar.txt.ocTransferId7437493.part', true, '/foo/bar.txt'),
		);
	}
}

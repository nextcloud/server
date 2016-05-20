<?php

namespace Test\Files\Storage\Wrapper;

use OC\Encryption\Util;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OC\User\Manager;
use Test\Files\Storage\Storage;

class EncryptionTest extends Storage {

	/**
	 * block size will always be 8192 for a PHP stream
	 * @see https://bugs.php.net/bug.php?id=21641
	 * @var integer
	 */
	protected $headerSize = 8192;

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

	/** @var  \OC\Memcache\ArrayCache | \PHPUnit_Framework_MockObject_MockObject */
	private $arrayCache;


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

		$this->arrayCache = $this->getMock('OC\Memcache\ArrayCache');
		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager = $this->getMockBuilder('\OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();

		$this->util = $this->getMock(
			'\OC\Encryption\Util',
			['getUidAndFilename', 'isFile', 'isExcluded'],
			[new View(), new Manager(), $this->groupManager, $this->config, $this->arrayCache]);
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
		$this->mount->expects($this->any())->method('getOption')->willReturnCallback(function ($option, $default) {
			if ($option === 'encrypt' && $default === true) {
				global $mockedMountPointEncryptionEnabled;
				if ($mockedMountPointEncryptionEnabled !== null) {
					return $mockedMountPointEncryptionEnabled;
				}
			}
			return true;
		});

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
					$this->encryptionManager, $this->util, $this->logger, $this->file, null, $this->keyStore, $this->update, $this->mountManager, $this->arrayCache
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
			->setMethods(['getId', 'getDisplayName', 'begin', 'end', 'encrypt', 'decrypt', 'update', 'shouldEncrypt', 'getUnencryptedBlockSize', 'isReadable', 'encryptAll', 'prepareDecryptAll', 'isReadyForUser'])
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
	 * @dataProvider dataTestGetMetaData
	 *
	 * @param string $path
	 * @param array $metaData
	 * @param bool $encrypted
	 * @param bool $unencryptedSizeSet
	 * @param int $storedUnencryptedSize
	 * @param array $expected
	 */
	public function testGetMetaData($path, $metaData, $encrypted, $unencryptedSizeSet, $storedUnencryptedSize, $expected) {

		$sourceStorage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()->getMock();

		$cache = $this->getMockBuilder('\OC\Files\Cache\Cache')
			->disableOriginalConstructor()->getMock();
		$cache->expects($this->any())
			->method('get')
			->willReturnCallback(
				function($path) use ($encrypted) {
					return ['encrypted' => $encrypted, 'path' => $path, 'size' => 0, 'fileid' => 1];
				}
			);

		$this->instance = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Encryption')
			->setConstructorArgs(
				[
					[
						'storage' => $sourceStorage,
						'root' => 'foo',
						'mountPoint' => '/',
						'mount' => $this->mount
					],
					$this->encryptionManager, $this->util, $this->logger, $this->file, null, $this->keyStore, $this->update, $this->mountManager, $this->arrayCache
				]
			)
			->setMethods(['getCache', 'verifyUnencryptedSize'])
			->getMock();

		if($unencryptedSizeSet) {
			$this->invokePrivate($this->instance, 'unencryptedSize', [[$path => $storedUnencryptedSize]]);
		}

		$fileEntry = $this->getMockBuilder('\OC\Files\Cache\Cache')
			->disableOriginalConstructor()->getMock();
		$sourceStorage->expects($this->once())->method('getMetaData')->with($path)
			->willReturn($metaData);
		$sourceStorage->expects($this->any())
			->method('getCache')
			->with($path)
			->willReturn($fileEntry);
		$fileEntry->expects($this->any())
			->method('get')
			->with($metaData['fileid']);

		$this->instance->expects($this->any())->method('getCache')->willReturn($cache);
		$this->instance->expects($this->any())->method('verifyUnencryptedSize')
			->with($path, 0)->willReturn($expected['size']);

		$result = $this->instance->getMetaData($path);
		if(isset($expected['encrypted'])) {
			$this->assertSame($expected['encrypted'], (bool)$result['encrypted']);

			if(isset($expected['encryptedVersion'])) {
				$this->assertSame($expected['encryptedVersion'], $result['encryptedVersion']);
			}
		}
		$this->assertSame($expected['size'], $result['size']);
	}

	public function dataTestGetMetaData() {
		return [
			['/test.txt', ['size' => 42, 'encrypted' => 2, 'encryptedVersion' => 2, 'fileid' => 1], true, true, 12, ['size' => 12, 'encrypted' => true, 'encryptedVersion' => 2]],
			['/test.txt', null, true, true, 12, null],
			['/test.txt', ['size' => 42, 'encrypted' => 0, 'fileid' => 1], false, false, 12, ['size' => 42, 'encrypted' => false]],
			['/test.txt', ['size' => 42, 'encrypted' => false, 'fileid' => 1], true, false, 12, ['size' => 12, 'encrypted' => true]]
		];
	}

	public function testFilesize() {
		$cache = $this->getMockBuilder('\OC\Files\Cache\Cache')
			->disableOriginalConstructor()->getMock();
		$cache->expects($this->any())
			->method('get')
			->willReturn(['encrypted' => true, 'path' => '/test.txt', 'size' => 0, 'fileid' => 1]);

		$this->instance = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Encryption')
			->setConstructorArgs(
				[
					[
						'storage' => $this->sourceStorage,
						'root' => 'foo',
						'mountPoint' => '/',
						'mount' => $this->mount
					],
					$this->encryptionManager, $this->util, $this->logger, $this->file, null, $this->keyStore, $this->update, $this->mountManager, $this->arrayCache
				]
			)
			->setMethods(['getCache', 'verifyUnencryptedSize'])
			->getMock();

		$this->instance->expects($this->any())->method('getCache')->willReturn($cache);
		$this->instance->expects($this->any())->method('verifyUnencryptedSize')
			->willReturn(42);


		$this->assertSame(42,
			$this->instance->filesize('/test.txt')
		);

	}

	/**
	 * @dataProvider dataTestVerifyUnencryptedSize
	 *
	 * @param int $encryptedSize
	 * @param int $unencryptedSize
	 * @param bool $failure
	 * @param int $expected
	 */
	public function testVerifyUnencryptedSize($encryptedSize, $unencryptedSize, $failure, $expected) {
		$sourceStorage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()->getMock();

		$this->instance = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Encryption')
			->setConstructorArgs(
				[
					[
						'storage' => $sourceStorage,
						'root' => 'foo',
						'mountPoint' => '/',
						'mount' => $this->mount
					],
					$this->encryptionManager, $this->util, $this->logger, $this->file, null, $this->keyStore, $this->update, $this->mountManager, $this->arrayCache
				]
			)
			->setMethods(['fixUnencryptedSize'])
			->getMock();

		$sourceStorage->expects($this->once())->method('filesize')->willReturn($encryptedSize);

		$this->instance->expects($this->any())->method('fixUnencryptedSize')
			->with('/test.txt', $encryptedSize, $unencryptedSize)
			->willReturnCallback(
				function() use ($failure, $expected) {
					if ($failure) {
						throw new \Exception();
					} else {
						return $expected;
					}
				}
			);

		$this->assertSame(
			$expected,
			$this->invokePrivate($this->instance, 'verifyUnencryptedSize', ['/test.txt', $unencryptedSize])
		);
	}

	public function dataTestVerifyUnencryptedSize() {
		return [
			[120, 80, false, 80],
			[120, 120, false, 80],
			[120, -1, false, 80],
			[120, -1, true, -1]
		];
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

	public function testCopyEncryption() {
		$this->instance->file_put_contents('source.txt', 'bar');
		$this->instance->copy('source.txt', 'target.txt');
		$this->assertSame('bar', $this->instance->file_get_contents('target.txt'));
		$targetMeta = $this->instance->getMetaData('target.txt');
		$sourceMeta = $this->instance->getMetaData('source.txt');
		$this->assertSame($sourceMeta['encrypted'], $targetMeta['encrypted']);
		$this->assertSame($sourceMeta['size'], $targetMeta['size']);
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
			->setConstructorArgs(
				[
					new View(),
					new Manager(),
					$this->groupManager,
					$this->config,
					$this->arrayCache
				]
			)->getMock();

		$instance = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Encryption')
			->setConstructorArgs(
				[
					[
						'storage' => $sourceStorage,
						'root' => 'foo',
						'mountPoint' => '/',
						'mount' => $this->mount
					],
					$this->encryptionManager, $util, $this->logger, $this->file, null, $this->keyStore, $this->update, $this->mountManager, $this->arrayCache
				]
			)
			->setMethods(['readFirstBlock', 'parseRawHeader'])
			->getMock();

		$instance->expects($this->once())->method(('parseRawHeader'))
			->willReturn([Util::HEADER_ENCRYPTION_MODULE_KEY => 'OC_DEFAULT_MODULE']);

		if ($strippedPathExists) {
			$instance->expects($this->once())->method('readFirstBlock')
				->with($strippedPath)->willReturn('');
		} else {
			$instance->expects($this->once())->method('readFirstBlock')
				->with($path)->willReturn('');
		}

		$util->expects($this->once())->method('stripPartialFileExtension')
			->with($path)->willReturn($strippedPath);
		$sourceStorage->expects($this->once())
			->method('file_exists')
			->with($strippedPath)
			->willReturn($strippedPathExists);

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

	/**
	 * test if getHeader adds the default module correctly to the header for
	 * legacy files
	 *
	 * @dataProvider dataTestGetHeaderAddLegacyModule
	 */
	public function testGetHeaderAddLegacyModule($header, $isEncrypted, $expected) {

		$sourceStorage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()->getMock();

		$util = $this->getMockBuilder('\OC\Encryption\Util')
			->setConstructorArgs([new View(), new Manager(), $this->groupManager, $this->config, $this->arrayCache])
			->getMock();

		$cache = $this->getMockBuilder('\OC\Files\Cache\Cache')
			->disableOriginalConstructor()->getMock();
		$cache->expects($this->any())
			->method('get')
			->willReturnCallback(function($path) use ($isEncrypted) {return ['encrypted' => $isEncrypted, 'path' => $path];});

		$instance = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Encryption')
			->setConstructorArgs(
				[
					[
						'storage' => $sourceStorage,
						'root' => 'foo',
						'mountPoint' => '/',
						'mount' => $this->mount
					],
					$this->encryptionManager, $util, $this->logger, $this->file, null, $this->keyStore, $this->update, $this->mountManager, $this->arrayCache
				]
			)
			->setMethods(['readFirstBlock', 'parseRawHeader', 'getCache'])
			->getMock();

		$instance->expects($this->once())->method(('parseRawHeader'))->willReturn($header);
		$instance->expects($this->any())->method('getCache')->willReturn($cache);

		$result = $this->invokePrivate($instance, 'getHeader', ['test.txt']);
		$this->assertSameSize($expected, $result);
		foreach ($result as $key => $value) {
			$this->assertArrayHasKey($key, $expected);
			$this->assertSame($expected[$key], $value);
		}
	}

	public function dataTestGetHeaderAddLegacyModule() {
		return [
			[['cipher' => 'AES-128'], true, ['cipher' => 'AES-128', Util::HEADER_ENCRYPTION_MODULE_KEY => 'OC_DEFAULT_MODULE']],
			[[], true, [Util::HEADER_ENCRYPTION_MODULE_KEY => 'OC_DEFAULT_MODULE']],
			[[], false, []],
		];
	}

	/**
	 * @dataProvider dataTestParseRawHeader
	 */
	public function testParseRawHeader($rawHeader, $expected) {
		$instance = new \OC\Files\Storage\Wrapper\Encryption(
					[
						'storage' => $this->sourceStorage,
						'root' => 'foo',
						'mountPoint' => '/',
						'mount' => $this->mount
					],
					$this->encryptionManager, $this->util, $this->logger, $this->file, null, $this->keyStore, $this->update, $this->mountManager, $this->arrayCache

			);

		$result = $this->invokePrivate($instance, 'parseRawHeader', [$rawHeader]);
		$this->assertSameSize($expected, $result);
		foreach ($result as $key => $value) {
			$this->assertArrayHasKey($key, $expected);
			$this->assertSame($expected[$key], $value);
		}
	}

	public function dataTestParseRawHeader() {
		return [
			[str_pad('HBEGIN:oc_encryption_module:0:HEND', $this->headerSize, '-', STR_PAD_RIGHT)
				, [Util::HEADER_ENCRYPTION_MODULE_KEY => '0']],
			[str_pad('HBEGIN:oc_encryption_module:0:custom_header:foo:HEND', $this->headerSize, '-', STR_PAD_RIGHT)
				, ['custom_header' => 'foo', Util::HEADER_ENCRYPTION_MODULE_KEY => '0']],
			[str_pad('HelloWorld', $this->headerSize, '-', STR_PAD_RIGHT), []],
			['', []],
			[str_pad('HBEGIN:oc_encryption_module:0', $this->headerSize, '-', STR_PAD_RIGHT)
				, []],
			[str_pad('oc_encryption_module:0:HEND', $this->headerSize, '-', STR_PAD_RIGHT)
				, []],
		];
	}

	public function dataCopyBetweenStorage() {
		return [
			[true, true, true],
			[true, false, false],
			[false, true, false],
			[false, false, false],
		];
	}

	public function testCopyBetweenStorageMinimumEncryptedVersion() {
		$storage2 = $this->getMockBuilder('OCP\Files\Storage')
			->disableOriginalConstructor()
			->getMock();

		$sourceInternalPath = $targetInternalPath = 'file.txt';
		$preserveMtime = $isRename = false;

		$storage2->expects($this->any())
			->method('fopen')
			->willReturnCallback(function($path, $mode) {
				$temp = \OC::$server->getTempManager();
				return fopen($temp->getTemporaryFile(), $mode);
			});
		$cache = $this->getMock('\OCP\Files\Cache\ICache');
		$cache->expects($this->once())
			->method('get')
			->with($sourceInternalPath)
			->willReturn(['encryptedVersion' => 0]);
		$storage2->expects($this->once())
			->method('getCache')
			->willReturn($cache);
		$this->encryptionManager->expects($this->any())
			->method('isEnabled')
			->willReturn(true);
		global $mockedMountPointEncryptionEnabled;
		$mockedMountPointEncryptionEnabled = true;

		$expectedCachePut = [
			'encrypted' => true,
		];
		$expectedCachePut['encryptedVersion'] = 1;

		$this->cache->expects($this->once())
			->method('put')
			->with($sourceInternalPath, $expectedCachePut);

		$this->invokePrivate($this->instance, 'copyBetweenStorage', [$storage2, $sourceInternalPath, $targetInternalPath, $preserveMtime, $isRename]);

		$this->assertFalse(false);
	}

	/**
	 * @dataProvider dataCopyBetweenStorage
	 *
	 * @param bool $encryptionEnabled
	 * @param bool $mountPointEncryptionEnabled
	 * @param bool $expectedEncrypted
	 */
	public function testCopyBetweenStorage($encryptionEnabled, $mountPointEncryptionEnabled, $expectedEncrypted) {
		$storage2 = $this->getMockBuilder('OCP\Files\Storage')
			->disableOriginalConstructor()
			->getMock();

		$sourceInternalPath = $targetInternalPath = 'file.txt';
		$preserveMtime = $isRename = false;

		$storage2->expects($this->any())
			->method('fopen')
			->willReturnCallback(function($path, $mode) {
				$temp = \OC::$server->getTempManager();
				return fopen($temp->getTemporaryFile(), $mode);
			});
		if($expectedEncrypted) {
			$cache = $this->getMock('\OCP\Files\Cache\ICache');
			$cache->expects($this->once())
				->method('get')
				->with($sourceInternalPath)
				->willReturn(['encryptedVersion' => 12345]);
			$storage2->expects($this->once())
				->method('getCache')
				->willReturn($cache);
		}
		$this->encryptionManager->expects($this->any())
			->method('isEnabled')
			->willReturn($encryptionEnabled);
		// FIXME can not overwrite the return after definition
//		$this->mount->expects($this->at(0))
//			->method('getOption')
//			->with('encrypt', true)
//			->willReturn($mountPointEncryptionEnabled);
		global $mockedMountPointEncryptionEnabled;
		$mockedMountPointEncryptionEnabled = $mountPointEncryptionEnabled;

		$expectedCachePut = [
			'encrypted' => $expectedEncrypted,
		];
		if($expectedEncrypted === true) {
			$expectedCachePut['encryptedVersion'] = 12345;
		}

		$this->arrayCache->expects($this->never())->method('set');

		$this->cache->expects($this->once())
			->method('put')
			->with($sourceInternalPath, $expectedCachePut);

		$this->invokePrivate($this->instance, 'copyBetweenStorage', [$storage2, $sourceInternalPath, $targetInternalPath, $preserveMtime, $isRename]);

		$this->assertFalse(false);
	}

	/**
	 * @dataProvider dataTestCopyBetweenStorageVersions
	 *
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @param bool $copyResult
	 * @param bool $encrypted
	 */
	public function  testCopyBetweenStorageVersions($sourceInternalPath, $targetInternalPath, $copyResult, $encrypted) {

		$sourceStorage = $this->getMockBuilder('OCP\Files\Storage')
			->disableOriginalConstructor()
			->getMock();

		$targetStorage = $this->getMockBuilder('OCP\Files\Storage')
			->disableOriginalConstructor()
			->getMock();

		$cache = $this->getMockBuilder('\OC\Files\Cache\Cache')
			->disableOriginalConstructor()->getMock();

		$mountPoint = '/mountPoint';

		/** @var \OC\Files\Storage\Wrapper\Encryption |\PHPUnit_Framework_MockObject_MockObject  $instance */
		$instance = $this->getMockBuilder('\OC\Files\Storage\Wrapper\Encryption')
			->setConstructorArgs(
				[
					[
						'storage' => $targetStorage,
						'root' => 'foo',
						'mountPoint' => $mountPoint,
						'mount' => $this->mount
					],
					$this->encryptionManager,
					$this->util,
					$this->logger,
					$this->file,
					null,
					$this->keyStore,
					$this->update,
					$this->mountManager,
					$this->arrayCache
				]
			)
			->setMethods(['updateUnencryptedSize', 'getCache'])
			->getMock();

		$targetStorage->expects($this->once())->method('copyFromStorage')
			->with($sourceStorage, $sourceInternalPath, $targetInternalPath)
			->willReturn($copyResult);

		$instance->expects($this->any())->method('getCache')
			->willReturn($cache);

		$this->arrayCache->expects($this->once())->method('set')
			->with('encryption_copy_version_' . $sourceInternalPath, true);

		if ($copyResult) {
			$cache->expects($this->once())->method('get')
				->with($sourceInternalPath)
				->willReturn(['encrypted' => $encrypted, 'size' => 42]);
			if ($encrypted) {
				$instance->expects($this->once())->method('updateUnencryptedSize')
					->with($mountPoint . $targetInternalPath, 42);
			} else {
				$instance->expects($this->never())->method('updateUnencryptedSize');
			}
		} else {
			$instance->expects($this->never())->method('updateUnencryptedSize');
		}

		$result = $this->invokePrivate(
			$instance,
			'copyBetweenStorage',
			[
				$sourceStorage,
				$sourceInternalPath,
				$targetInternalPath,
				false,
				false
			]
		);

		$this->assertSame($copyResult, $result);
	}

	public function dataTestCopyBetweenStorageVersions() {
		return [
			['/files/foo.txt', '/files_versions/foo.txt.768743', true, true],
			['/files/foo.txt', '/files_versions/foo.txt.768743', true, false],
			['/files/foo.txt', '/files_versions/foo.txt.768743', false, true],
			['/files/foo.txt', '/files_versions/foo.txt.768743', false, false],
			['/files_versions/foo.txt.6487634', '/files/foo.txt', true, true],
			['/files_versions/foo.txt.6487634', '/files/foo.txt', true, false],
			['/files_versions/foo.txt.6487634', '/files/foo.txt', false, true],
			['/files_versions/foo.txt.6487634', '/files/foo.txt', false, false],

		];
	}

	/**
	 * @dataProvider dataTestIsVersion
	 * @param string $path
	 * @param bool $expected
	 */
	public function testIsVersion($path, $expected) {
		$this->assertSame($expected,
			$this->invokePrivate($this->instance, 'isVersion', [$path])
		);
	}

	public function dataTestIsVersion() {
		return [
			['files_versions/foo', true],
			['/files_versions/foo', true],
			['//files_versions/foo', true],
			['files/versions/foo', false],
			['files/files_versions/foo', false],
			['files_versions_test/foo', false],
		];
	}

}

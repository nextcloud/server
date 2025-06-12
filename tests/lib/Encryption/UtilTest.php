<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Test\Encryption;

use OC\Encryption\Exceptions\EncryptionHeaderKeyExistsException;
use OC\Encryption\Util;
use OC\Files\View;
use OCP\Encryption\IEncryptionModule;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use Test\TestCase;

class UtilTest extends TestCase {
	/**
	 * block size will always be 8192 for a PHP stream
	 *
	 * @see https://bugs.php.net/bug.php?id=21641
	 */
	protected static int $headerSize = 8192;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $view;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IUserManager */
	protected $userManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IGroupManager */
	protected $groupManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IConfig */
	private $config;
	private Util $util;

	protected function setUp(): void {
		parent::setUp();
		$this->view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()
			->getMock();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->config = $this->createMock(IConfig::class);

		$this->util = new Util(
			$this->view,
			$this->userManager,
			$this->groupManager,
			$this->config
		);
	}

	/**
	 * @dataProvider providesHeadersForEncryptionModule
	 */
	public function testGetEncryptionModuleId($expected, $header): void {
		$id = $this->util->getEncryptionModuleId($header);
		$this->assertEquals($expected, $id);
	}

	public static function providesHeadersForEncryptionModule(): array {
		return [
			['', []],
			['', ['1']],
			[2, ['oc_encryption_module' => 2]],
		];
	}

	/**
	 * @dataProvider providesHeaders
	 */
	public function testCreateHeader($expected, $header, $moduleId): void {
		$em = $this->createMock(IEncryptionModule::class);
		$em->expects($this->any())->method('getId')->willReturn($moduleId);

		$result = $this->util->createHeader($header, $em);
		$this->assertEquals($expected, $result);
	}

	public static function providesHeaders(): array {
		return [
			[str_pad('HBEGIN:oc_encryption_module:0:HEND', self::$headerSize, '-', STR_PAD_RIGHT)
				, [], '0'],
			[str_pad('HBEGIN:oc_encryption_module:0:custom_header:foo:HEND', self::$headerSize, '-', STR_PAD_RIGHT)
				, ['custom_header' => 'foo'], '0'],
		];
	}


	public function testCreateHeaderFailed(): void {
		$this->expectException(EncryptionHeaderKeyExistsException::class);


		$header = ['header1' => 1, 'header2' => 2, 'oc_encryption_module' => 'foo'];

		$em = $this->createMock(IEncryptionModule::class);
		$em->expects($this->any())->method('getId')->willReturn('moduleId');

		$this->util->createHeader($header, $em);
	}

	/**
	 * @dataProvider providePathsForTestIsExcluded
	 */
	public function testIsExcluded($path, $keyStorageRoot, $expected): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('core', 'encryption_key_storage_root', '')
			->willReturn($keyStorageRoot);
		$this->userManager
			->expects($this->any())
			->method('userExists')
			->willReturnCallback([$this, 'isExcludedCallback']);

		$this->assertSame($expected,
			$this->util->isExcluded($path)
		);
	}

	public static function providePathsForTestIsExcluded(): array {
		return [
			['/files_encryption', '', true],
			['files_encryption/foo.txt', '', true],
			['test/foo.txt', '', false],
			['/user1/files_encryption/foo.txt', '', true],
			['/user1/files/foo.txt', '', false],
			['/keyStorage/user1/files/foo.txt', 'keyStorage', true],
			['/keyStorage/files_encryption', '/keyStorage', true],
			['keyStorage/user1/files_encryption', '/keyStorage/', true],

		];
	}

	public function isExcludedCallback() {
		$args = func_get_args();
		if ($args[0] === 'user1') {
			return true;
		}

		return false;
	}

	/**
	 * @dataProvider dataTestIsFile
	 */
	public function testIsFile($path, $expected): void {
		$this->assertSame($expected,
			$this->util->isFile($path)
		);
	}

	public static function dataTestIsFile(): array {
		return [
			['/user/files/test.txt', true],
			['/user/files', true],
			['/user/files_versions/test.txt', false],
			['/user/foo/files/test.txt', false],
			['/files/foo/files/test.txt', false],
			['/user', false],
			['/user/test.txt', false],
		];
	}

	/**
	 * @dataProvider dataTestStripPartialFileExtension
	 *
	 * @param string $path
	 * @param string $expected
	 */
	public function testStripPartialFileExtension($path, $expected): void {
		$this->assertSame($expected,
			$this->util->stripPartialFileExtension($path));
	}

	public static function dataTestStripPartialFileExtension(): array {
		return [
			['/foo/test.txt', '/foo/test.txt'],
			['/foo/test.txt.part', '/foo/test.txt'],
			['/foo/test.txt.ocTransferId7567846853.part', '/foo/test.txt'],
			['/foo/test.txt.ocTransferId7567.part', '/foo/test.txt'],
		];
	}

	/**
	 * @dataProvider dataTestParseRawHeader
	 */
	public function testParseRawHeader($rawHeader, $expected): void {
		$result = $this->util->parseRawHeader($rawHeader);
		$this->assertSameSize($expected, $result);
		foreach ($result as $key => $value) {
			$this->assertArrayHasKey($key, $expected);
			$this->assertSame($expected[$key], $value);
		}
	}

	public static function dataTestParseRawHeader(): array {
		return [
			[str_pad('HBEGIN:oc_encryption_module:0:HEND', self::$headerSize, '-', STR_PAD_RIGHT)
				, [Util::HEADER_ENCRYPTION_MODULE_KEY => '0']],
			[str_pad('HBEGIN:oc_encryption_module:0:custom_header:foo:HEND', self::$headerSize, '-', STR_PAD_RIGHT)
				, ['custom_header' => 'foo', Util::HEADER_ENCRYPTION_MODULE_KEY => '0']],
			[str_pad('HelloWorld', self::$headerSize, '-', STR_PAD_RIGHT), []],
			['', []],
			[str_pad('HBEGIN:oc_encryption_module:0', self::$headerSize, '-', STR_PAD_RIGHT)
				, []],
			[str_pad('oc_encryption_module:0:HEND', self::$headerSize, '-', STR_PAD_RIGHT)
				, []],
		];
	}

	/**
	 * @dataProvider dataTestGetFileKeyDir
	 *
	 * @param bool $isSystemWideMountPoint
	 * @param string $storageRoot
	 * @param string $expected
	 */
	public function testGetFileKeyDir($isSystemWideMountPoint, $storageRoot, $expected): void {
		$path = '/user1/files/foo/bar.txt';
		$owner = 'user1';
		$relativePath = '/foo/bar.txt';

		$util = $this->getMockBuilder(Util::class)
			->onlyMethods(['isSystemWideMountPoint', 'getUidAndFilename', 'getKeyStorageRoot'])
			->setConstructorArgs([
				$this->view,
				$this->userManager,
				$this->groupManager,
				$this->config
			])
			->getMock();

		$util->expects($this->once())->method('getKeyStorageRoot')
			->willReturn($storageRoot);
		$util->expects($this->once())->method('isSystemWideMountPoint')
			->willReturn($isSystemWideMountPoint);
		$util->expects($this->once())->method('getUidAndFilename')
			->with($path)->willReturn([$owner, $relativePath]);

		$this->assertSame($expected,
			$util->getFileKeyDir('OC_DEFAULT_MODULE', $path)
		);
	}

	public static function dataTestGetFileKeyDir(): array {
		return [
			[false, '', '/user1/files_encryption/keys/foo/bar.txt/OC_DEFAULT_MODULE/'],
			[true, '', '/files_encryption/keys/foo/bar.txt/OC_DEFAULT_MODULE/'],
			[false, 'newStorageRoot', '/newStorageRoot/user1/files_encryption/keys/foo/bar.txt/OC_DEFAULT_MODULE/'],
			[true, 'newStorageRoot', '/newStorageRoot/files_encryption/keys/foo/bar.txt/OC_DEFAULT_MODULE/'],
		];
	}
}

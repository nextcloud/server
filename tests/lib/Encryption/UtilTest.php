<?php

namespace Test\Encryption;

use OC\Encryption\Util;
use Test\TestCase;

class UtilTest extends TestCase {

	/**
	 * block size will always be 8192 for a PHP stream
	 * @see https://bugs.php.net/bug.php?id=21641
	 * @var integer
	 */
	protected $headerSize = 8192;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $view;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var  \OC\Encryption\Util */
	private $util;

	public function setUp() {
		parent::setUp();
		$this->view = $this->getMockBuilder('OC\Files\View')
			->disableOriginalConstructor()
			->getMock();

		$this->userManager = $this->getMockBuilder('OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();

		$this->groupManager = $this->getMockBuilder('OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();

		$this->config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();

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
	public function testGetEncryptionModuleId($expected, $header) {
		$id = $this->util->getEncryptionModuleId($header);
		$this->assertEquals($expected, $id);
	}

	public function providesHeadersForEncryptionModule() {
		return [
			['', []],
			['', ['1']],
			[2, ['oc_encryption_module' => 2]],
		];
	}

	/**
	 * @dataProvider providesHeaders
	 */
	public function testCreateHeader($expected, $header, $moduleId) {

		$em = $this->getMock('\OCP\Encryption\IEncryptionModule');
		$em->expects($this->any())->method('getId')->willReturn($moduleId);

		$result = $this->util->createHeader($header, $em);
		$this->assertEquals($expected, $result);
	}

	public function providesHeaders() {
		return [
			[str_pad('HBEGIN:oc_encryption_module:0:HEND', $this->headerSize, '-', STR_PAD_RIGHT)
				, [], '0'],
			[str_pad('HBEGIN:oc_encryption_module:0:custom_header:foo:HEND', $this->headerSize, '-', STR_PAD_RIGHT)
				, ['custom_header' => 'foo'], '0'],
		];
	}

	/**
	 * @expectedException \OC\Encryption\Exceptions\EncryptionHeaderKeyExistsException
	 */
	public function testCreateHeaderFailed() {

		$header = array('header1' => 1, 'header2' => 2, 'oc_encryption_module' => 'foo');

		$em = $this->getMock('\OCP\Encryption\IEncryptionModule');
		$em->expects($this->any())->method('getId')->willReturn('moduleId');

		$this->util->createHeader($header, $em);
	}

	/**
	 * @dataProvider providePathsForTestIsExcluded
	 */
	public function testIsExcluded($path, $keyStorageRoot, $expected) {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('core', 'encryption_key_storage_root', '')
			->willReturn($keyStorageRoot);
		$this->userManager
			->expects($this->any())
			->method('userExists')
			->will($this->returnCallback(array($this, 'isExcludedCallback')));

		$this->assertSame($expected,
			$this->util->isExcluded($path)
		);
	}

	public function providePathsForTestIsExcluded() {
		return array(
			array('/files_encryption', '', true),
			array('files_encryption/foo.txt', '', true),
			array('test/foo.txt', '', false),
			array('/user1/files_encryption/foo.txt', '', true),
			array('/user1/files/foo.txt', '', false),
			array('/keyStorage/user1/files/foo.txt', 'keyStorage', true),
			array('/keyStorage/files_encryption', '/keyStorage', true),
			array('keyStorage/user1/files_encryption', '/keyStorage/', true),

		);
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
	public function testIsFile($path, $expected) {
		$this->assertSame($expected,
			$this->util->isFile($path)
		);
	}

	public function dataTestIsFile() {
		return array(
			array('/user/files/test.txt', true),
			array('/user/files', true),
			array('/user/files_versions/test.txt', false),
			array('/user/foo/files/test.txt', false),
			array('/files/foo/files/test.txt', false),
			array('/user', false),
			array('/user/test.txt', false),
		);
	}

	/**
	 * @dataProvider dataTestStripPartialFileExtension
	 *
	 * @param string $path
	 * @param string $expected
	 */
	public function testStripPartialFileExtension($path, $expected) {
		$this->assertSame($expected,
			$this->util->stripPartialFileExtension($path));
	}

	public function dataTestStripPartialFileExtension() {
		return array(
			array('/foo/test.txt', '/foo/test.txt'),
			array('/foo/test.txt.part', '/foo/test.txt'),
			array('/foo/test.txt.ocTransferId7567846853.part', '/foo/test.txt'),
			array('/foo/test.txt.ocTransferId7567.part', '/foo/test.txt'),
		);
	}

}

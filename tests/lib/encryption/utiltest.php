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

	public function setUp() {
		parent::setUp();
		$this->view = $this->getMockBuilder('OC\Files\View')
			->disableOriginalConstructor()
			->getMock();

		$this->userManager = $this->getMockBuilder('OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @dataProvider providesHeadersForEncryptionModule
	 */
	public function testGetEncryptionModuleId($expected, $header) {
		$u = new Util($this->view, $this->userManager);
		$id = $u->getEncryptionModuleId($header);
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
	public function testReadHeader($header, $expected, $moduleId) {
		$expected['oc_encryption_module'] = $moduleId;
		$u = new Util($this->view, $this->userManager);
		$result = $u->readHeader($header);
		$this->assertSameSize($expected, $result);
		foreach ($expected as $key => $value) {
			$this->assertArrayHasKey($key, $result);
			$this->assertSame($value, $result[$key]);
		}
	}

	/**
	 * @dataProvider providesHeaders
	 */
	public function testCreateHeader($expected, $header, $moduleId) {

		$em = $this->getMock('\OCP\Encryption\IEncryptionModule');
		$em->expects($this->any())->method('getId')->willReturn($moduleId);

		$u = new Util($this->view, $this->userManager);
		$result = $u->createHeader($header, $em);
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

		$u = new Util($this->view, $this->userManager);
		$u->createHeader($header, $em);
	}

}

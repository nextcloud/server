<?php
/**
 * @author Clark Tomlinson  <fallen013@gmail.com>
 * @since 3/9/15, 2:56 PM
 * @link http:/www.clarkt.com
 * @copyright Clark Tomlinson © 2015
 *
 */

namespace OCA\Encryption\Tests;


use OCA\Encryption\Migrator;
use Test\TestCase;

class MigratorTest extends TestCase {

	/**
	 * @var Migrator
	 */
	private $instance;

	/**
	 *
	 */
	public function testGetStatus() {
		$this->assertFalse($this->instance->getStatus('admin'));
	}

	/**
	 *
	 */
	public function testBeginMigration() {
		$this->assertTrue($this->instance->beginMigration());
	}

	/**
	 *
	 */
	public function testSetMigrationStatus() {
		$this->assertTrue(\Test_Helper::invokePrivate($this->instance,
			'setMigrationStatus',
			['0', '-1'])
		);
	}

	/**
	 *
	 */
	protected function setUp() {
		parent::setUp();

		$cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')->disableOriginalConstructor()->getMock();
		$this->instance = new Migrator($this->getMock('OCP\IConfig'),
			$this->getMock('OCP\IUserManager'),
			$this->getMock('OCP\ILogger'),
			$cryptMock);
	}


}

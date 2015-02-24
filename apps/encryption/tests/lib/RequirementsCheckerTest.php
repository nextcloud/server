<?php
/**
 * @author Clark Tomlinson  <fallen013@gmail.com>
 * @since 3/6/15, 10:36 AM
 * @link http:/www.clarkt.com
 * @copyright Clark Tomlinson © 2015
 *
 */

namespace OCA\Encryption\Tests;


use OCA\Encryption\RequirementsChecker;
use Test\TestCase;

class RequirementsCheckerTest extends TestCase {
	/**
	 * @var RequirementsChecker
	 */
	private $instance;

	/**
	 *
	 */
	protected function setUp() {
		parent::setUp();
		$log = $this->getMock('OCP\ILogger');
		$crypt = $this->getMockBuilder('OCA\Encryption\Crypt')
			->disableOriginalConstructor()
			->getMock();
		$crypt
		->method('getOpenSSLPkey')
		->will($this->returnValue(true));
		$this->instance = new RequirementsChecker($crypt, $log);
	}

	/**
	 *
	 */
	public function testCanCheckConfigration() {
		$this->assertTrue($this->instance->checkConfiguration());
	}

	/**
	 *
	 */
	public function testCanCheckRequiredExtensions() {
		$this->assertTrue($this->instance->checkExtensions());
	}

}

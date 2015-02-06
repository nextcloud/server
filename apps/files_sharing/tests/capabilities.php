<?php
/**
  * Copyright (c) 2015 Roeland Jago Douma <roeland@famdouma.nl>
  * This file is licensed under the Affero General Public License version 3 or
  * later.
  * See the COPYING-README file.
  */

use OCA\Files\Share\Tests;
use OCA\Files_sharing\Tests\TestCase;

/**
 * Class Test_Files_Sharing_Capabilties
 */
class Test_Files_Sharing_Capabilities extends \Test\TestCase {

	/**
	 * Test for the general part in each return statement and assert
	 */
	function getFilesSharingPart($data) {
		$this->assertArrayHasKey('capabilities', $data);
		$this->assertArrayHasKey('files_sharing', $data['capabilities']);
		return $data['capabilities']['files_sharing'];
	}

	/**
	 * Create a mock config object and insert the values in $map tot the getAppValue
	 * function. Then obtain the capabilities and extract the first few
	 * levels in the array
	 */
	function getResults($map) {
		$stub = $this->getMockBuilder('\OCP\IConfig')->disableOriginalConstructor()->getMock();
		$stub->method('getAppValue')->will($this->returnValueMap($map));
		$cap = new \OCA\Files_Sharing\Capabilities($stub);
		$result = $this->getFilesSharingPart($cap->getCaps()->getData());
		return $result;
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_no_link_sharing() {
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'no'),
		);
		$result = $this->getResults($map);
		$this->assertFalse($result['public']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_only_link_sharing() {
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
		);
		$result = $this->getResults($map);
		$this->assertInternalType('array', $result['public']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_link_password() {
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
			array('core', 'shareapi_enforce_links_password', 'yes', 'yes'),
		);
		$result = $this->getResults($map);
		$this->assertArrayHasKey('password_enforced', $result['public']);
		$this->assertTrue($result['public']['password_enforced']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_link_no_password() {
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
			array('core', 'shareapi_enforce_links_password', 'yes', 'no'),
		);
		$result = $this->getResults($map);
		$this->assertArrayHasKey('password_enforced', $result['public']);
		$this->assertFalse($result['public']['password_enforced']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_link_no_expire_date() {
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
			array('core', 'shareapi_default_expire_date', 'yes', 'no'),
		);
		$result = $this->getResults($map);
		$this->assertArrayHasKey('expire_date', $result['public']);
		$this->assertFalse($result['public']['expire_date']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_link_expire_date() {
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
			array('core', 'shareapi_default_expire_date', 'yes', 'yes'),
			array('core', 'shareapi_expire_after_n_days', false, 0),
			array('core', 'shareapi_enforce_expire_date', 'yes', 'no'),
		);
		$result = $this->getResults($map);
		$this->assertArrayHasKey('expire_date', $result['public']);
		$this->assertInternalType('array', $result['public']['expire_date']);
		$this->assertInternalType('int', $result['public']['expire_date']['days']);
		$this->assertFalse($result['public']['expire_date']['enforce']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_link_expire_date_enforced() {
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
			array('core', 'shareapi_default_expire_date', 'yes', 'yes'),
			array('core', 'shareapi_expire_after_n_days', false, 0),
			array('core', 'shareapi_enforce_expire_date', 'yes', 'yes'),
		);
		$result = $this->getResults($map);
		$this->assertArrayHasKey('expire_date', $result['public']);
		$this->assertInternalType('array', $result['public']['expire_date']);
		$this->assertInternalType('int', $result['public']['expire_date']['days']);
		$this->assertTrue($result['public']['expire_date']['enforce']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_link_send_mail() {
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
			array('core', 'shareapi_allow_public_notification', 'yes', 'yes'),
		);
		$result = $this->getResults($map);
		$this->assertTrue($result['public']['send_mail']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_link_no_send_mail() {
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
			array('core', 'shareapi_allow_public_notification', 'yes', 'no'),
		);
		$result = $this->getResults($map);
		$this->assertFalse($result['public']['send_mail']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_user_send_mail() {
		$map = array(
			array('core', 'shareapi_allow_mail_notification', 'yes', 'yes'),
		);
		$result = $this->getResults($map);
		$this->assertTrue($result['user']['send_mail']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_user_no_send_mail() {
		$map = array(
			array('core', 'shareapi_allow_mail_notification', 'yes', 'no'),
		);
		$result = $this->getResults($map);
		$this->assertFalse($result['user']['send_mail']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_resharing() {
		$map = array(
			array('core', 'shareapi_allow_resharing', 'yes', 'yes'),
		);
		$result = $this->getResults($map);
		$this->assertTrue($result['resharing']);
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function test_no_resharing() {
		$map = array(
			array('core', 'shareapi_allow_resharing', 'yes', 'no'),
		);
		$result = $this->getResults($map);
		$this->assertFalse($result['resharing']);
	}
}

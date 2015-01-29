<?php
/**
  * Copyright (c) Roeland Jago Douma <roeland@famdouma.nl>
  * This file is licensed under the Affero General Public License version 3 or
  * later.
  * See the COPYING-README file.
  */

use OCA\Files\Share;
use OCA\Files_sharing\Tests\TestCase;

/**
 * Class Test_Files_Sharing_Capabilties
 */
class Test_Files_Sharing_Capabilities extends \PHPUnit_Framework_TestCase {

	/**
	 * Test for the general part in each return statement and assert
	 */
	function getFilesPart($data) {
		$this->assertArrayHasKey('capabilities', $data);
		$this->assertArrayHasKey('files', $data['capabilities']);
		return $data['capabilities']['files'];
	}

	/**
	 * Extract the sharing part and some asserts
	 */
	function getSharing($data) {
		$this->assertCount(1, $data);
		$this->assertArrayHasKey('sharing', $data);
		return $data['sharing'];
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
		$result = $this->getFilesPart($cap->getCaps()->getData());
		return $result;
	}

	/**
	 * @covers OCA\Files_Sharing\Capabilities::getCaps
	 */
	public function testCapabilities() {
		/*
		 * Test for no link sharing
		 */
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'no')
		);
		$result = $this->getResults($map);
		$this->assertEmpty($result);

		/*
		 * Test only link sharing
		 */
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
			array('core', 'shareapi_enforce_links_password', 'yes', 'no'),
			array('core', 'shareapi_allow_public_upload', 'yes', 'no')
		);
		$result = $this->getSharing($this->getResults($map));
		$this->assertCount(1, $result);
		$this->assertArrayHasKey('allow_links', $result);

		/*
		 * Test link sharing with enforced passwords
		 */
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
			array('core', 'shareapi_enforce_links_password', 'yes', 'yes'),
			array('core', 'shareapi_allow_public_upload', 'yes', 'no')
		);
		$result = $this->getSharing($this->getResults($map));
		$this->assertCount(2, $result);
		$this->assertArrayHasKey('allow_links', $result);
		$this->assertArrayHasKey('enforce_links_password', $result);

		/*
		 * Test link sharing with public upload
		 */
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
			array('core', 'shareapi_enforce_links_password', 'yes', 'no'),
			array('core', 'shareapi_allow_public_upload', 'yes', 'yes')
		);
		$result = $this->getSharing($this->getResults($map));
		$this->assertCount(2, $result);
		$this->assertArrayHasKey('allow_links', $result);
		$this->assertArrayHasKey('allow_public_upload', $result);

		/*
		 * Test link sharing with all options on
		 */
		$map = array(
			array('core', 'shareapi_allow_links', 'yes', 'yes'),
			array('core', 'shareapi_enforce_links_password', 'yes', 'yes'),
			array('core', 'shareapi_allow_public_upload', 'yes', 'yes')
		);
		$result = $this->getSharing($this->getResults($map));
		$this->assertCount(3, $result);
		$this->assertArrayHasKey('allow_links', $result);
		$this->assertArrayHasKey('enforce_links_password', $result);
		$this->assertArrayHasKey('allow_public_upload', $result);
	}
}

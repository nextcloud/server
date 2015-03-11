<?php
/**
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing\Tests;

use OCA\Files_Sharing\Capabilities;
use OCA\Files_Sharing\Tests\TestCase;

/**
 * Class FilesSharingCapabilitiesTest
 */
class FilesSharingCapabilitiesTest extends \Test\TestCase {

	/**
	 * Test for the general part in each return statement and assert.
	 * Strip of the general part on the way.
	 *
	 * @param string[] $data Capabilities
	 * @return string[]
	 */
	private function getFilesSharingPart(array $data) {
		$this->assertArrayHasKey('capabilities', $data);
		$this->assertArrayHasKey('files_sharing', $data['capabilities']);
		return $data['capabilities']['files_sharing'];
	}

	/**
	 * Create a mock config object and insert the values in $map tot the getAppValue
	 * function. Then obtain the capabilities and extract the first few
	 * levels in the array
	 *
	 * @param (string[])[] $map Map of arguments to return types for the getAppValue function in the mock
	 * @return string[]
	 */
	private function getResults(array $map) {
		$stub = $this->getMockBuilder('\OCP\IConfig')->disableOriginalConstructor()->getMock();
		$stub->method('getAppValue')->will($this->returnValueMap($map));
		$cap = new Capabilities($stub);
		$result = $this->getFilesSharingPart($cap->getCaps()->getData());
		return $result;
	}

	public function testNoLinkSharing() {
		$map = [
			['core', 'shareapi_allow_links', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertInternalType('array', $result['public']);
		$this->assertFalse($result['public']['enabled']);
	}

	public function testOnlyLinkSharing() {
		$map = [
			['core', 'shareapi_allow_links', 'yes', 'yes'],
		];
		$result = $this->getResults($map);
		$this->assertInternalType('array', $result['public']);
		$this->assertTrue($result['public']['enabled']);
	}

	public function testLinkPassword() {
		$map = [
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_enforce_links_password', 'yes', 'yes'],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('password', $result['public']);
		$this->assertArrayHasKey('enforced', $result['public']['password']);
		$this->assertTrue($result['public']['password']['enforced']);
	}

	public function testLinkNoPassword() {
		$map = [
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_enforce_links_password', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('password', $result['public']);
		$this->assertArrayHasKey('enforced', $result['public']['password']);
		$this->assertFalse($result['public']['password']['enforced']);
	}

	public function testLinkNoExpireDate() {
		$map = [
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_default_expire_date', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('expire_date', $result['public']);
		$this->assertInternalType('array', $result['public']['expire_date']);
		$this->assertFalse($result['public']['expire_date']['enabled']);
	}

	public function testLinkExpireDate() {
		$map = [
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_default_expire_date', 'yes', 'yes'],
			['core', 'shareapi_expire_after_n_days', '7', '7'],
			['core', 'shareapi_enforce_expire_date', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('expire_date', $result['public']);
		$this->assertInternalType('array', $result['public']['expire_date']);
		$this->assertTrue($result['public']['expire_date']['enabled']);
		$this->assertArrayHasKey('days', $result['public']['expire_date']);
		$this->assertFalse($result['public']['expire_date']['enforced']);
	}

	public function testLinkExpireDateEnforced() {
		$map = [
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_default_expire_date', 'yes', 'yes'],
			['core', 'shareapi_enforce_expire_date', 'yes', 'yes'],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('expire_date', $result['public']);
		$this->assertInternalType('array', $result['public']['expire_date']);
		$this->assertTrue($result['public']['expire_date']['enforced']);
	}

	public function testLinkSendMail() {
		$map = [
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_allow_public_notification', 'yes', 'yes'],
		];
		$result = $this->getResults($map);
		$this->assertTrue($result['public']['send_mail']);
	}

	public function testLinkNoSendMail() {
		$map = [
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_allow_public_notification', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertFalse($result['public']['send_mail']);
	}

	public function testUserSendMail() {
		$map = [
			['core', 'shareapi_allow_mail_notification', 'yes', 'yes'],
		];
		$result = $this->getResults($map);
		$this->assertTrue($result['user']['send_mail']);
	}

	public function testUserNoSendMail() {
		$map = [
			['core', 'shareapi_allow_mail_notification', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertFalse($result['user']['send_mail']);
	}

	public function testResharing() {
		$map = [
			['core', 'shareapi_allow_resharing', 'yes', 'yes'],
		];
		$result = $this->getResults($map);
		$this->assertTrue($result['resharing']);
	}

	public function testNoResharing() {
		$map = [
			['core', 'shareapi_allow_resharing', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertFalse($result['resharing']);
	}
}

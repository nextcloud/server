<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\KnownUser\KnownUserService;
use OC\Share20\Manager;
use OC\Share20\ShareDisableChecker;
use OCA\Files_Sharing\Capabilities;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Share\IProviderFactory;
use Psr\Log\LoggerInterface;

/**
 * Class CapabilitiesTest
 *
 * @group DB
 */
class CapabilitiesTest extends \Test\TestCase {

	/**
	 * Test for the general part in each return statement and assert.
	 * Strip of the general part on the way.
	 *
	 * @param string[] $data Capabilities
	 * @return string[]
	 */
	private function getFilesSharingPart(array $data) {
		$this->assertArrayHasKey('files_sharing', $data);
		return $data['files_sharing'];
	}

	/**
	 * Create a mock config object and insert the values in $map to the getAppValue
	 * function. Then obtain the capabilities and extract the first few
	 * levels in the array
	 *
	 * @param (string[])[] $map Map of arguments to return types for the getAppValue function in the mock
	 * @return string[]
	 */
	private function getResults(array $map) {
		$config = $this->getMockBuilder(IConfig::class)->disableOriginalConstructor()->getMock();
		$config->method('getAppValue')->willReturnMap($map);
		$shareManager = new Manager(
			$this->createMock(LoggerInterface::class),
			$config,
			$this->createMock(ISecureRandom::class),
			$this->createMock(IHasher::class),
			$this->createMock(IMountManager::class),
			$this->createMock(IGroupManager::class),
			$this->createMock(IFactory::class),
			$this->createMock(IProviderFactory::class),
			$this->createMock(IUserManager::class),
			$this->createMock(IRootFolder::class),
			$this->createMock(IMailer::class),
			$this->createMock(IURLGenerator::class),
			$this->createMock(\OC_Defaults::class),
			$this->createMock(IEventDispatcher::class),
			$this->createMock(IUserSession::class),
			$this->createMock(KnownUserService::class),
			$this->createMock(ShareDisableChecker::class),
			$this->createMock(IDateTimeZone::class),
		);
		$cap = new Capabilities($config, $shareManager);
		$result = $this->getFilesSharingPart($cap->getCapabilities());
		return $result;
	}

	public function testEnabledSharingAPI(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
		];
		$result = $this->getResults($map);
		$this->assertTrue($result['api_enabled']);
		$this->assertArrayHasKey('public', $result);
		$this->assertArrayHasKey('user', $result);
		$this->assertArrayHasKey('resharing', $result);
	}

	public function testDisabledSharingAPI(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertFalse($result['api_enabled']);
		$this->assertFalse($result['public']['enabled']);
		$this->assertFalse($result['user']['send_mail']);
		$this->assertFalse($result['resharing']);
	}

	public function testNoLinkSharing(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_links', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertIsArray($result['public']);
		$this->assertFalse($result['public']['enabled']);
	}

	public function testOnlyLinkSharing(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
		];
		$result = $this->getResults($map);
		$this->assertIsArray($result['public']);
		$this->assertTrue($result['public']['enabled']);
	}

	public function testLinkPassword(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
			['core', 'shareapi_enforce_links_password', 'no', 'yes'],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('password', $result['public']);
		$this->assertArrayHasKey('enforced', $result['public']['password']);
		$this->assertTrue($result['public']['password']['enforced']);
	}

	public function testLinkNoPassword(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
			['core', 'shareapi_enforce_links_password', 'no', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('password', $result['public']);
		$this->assertArrayHasKey('enforced', $result['public']['password']);
		$this->assertFalse($result['public']['password']['enforced']);
	}

	public function testLinkNoExpireDate(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_default_expire_date', 'no', 'no'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('expire_date', $result['public']);
		$this->assertIsArray($result['public']['expire_date']);
		$this->assertFalse($result['public']['expire_date']['enabled']);
	}

	public function testLinkExpireDate(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_default_expire_date', 'no', 'yes'],
			['core', 'shareapi_expire_after_n_days', '7', '7'],
			['core', 'shareapi_enforce_expire_date', 'no', 'no'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('expire_date', $result['public']);
		$this->assertIsArray($result['public']['expire_date']);
		$this->assertTrue($result['public']['expire_date']['enabled']);
		$this->assertArrayHasKey('days', $result['public']['expire_date']);
		$this->assertFalse($result['public']['expire_date']['enforced']);
	}

	public function testLinkExpireDateEnforced(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_default_expire_date', 'no', 'yes'],
			['core', 'shareapi_enforce_expire_date', 'no', 'yes'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('expire_date', $result['public']);
		$this->assertIsArray($result['public']['expire_date']);
		$this->assertTrue($result['public']['expire_date']['enforced']);
	}

	public function testLinkSendMail(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_allow_public_notification', 'no', 'yes'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
		];
		$result = $this->getResults($map);
		$this->assertTrue($result['public']['send_mail']);
	}

	public function testLinkNoSendMail(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_allow_public_notification', 'no', 'no'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
		];
		$result = $this->getResults($map);
		$this->assertFalse($result['public']['send_mail']);
	}

	public function testResharing(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_resharing', 'yes', 'yes'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
		];
		$result = $this->getResults($map);
		$this->assertTrue($result['resharing']);
	}

	public function testNoResharing(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_resharing', 'yes', 'no'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
		];
		$result = $this->getResults($map);
		$this->assertFalse($result['resharing']);
	}

	public function testLinkPublicUpload(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_allow_public_upload', 'yes', 'yes'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
		];
		$result = $this->getResults($map);
		$this->assertTrue($result['public']['upload']);
		$this->assertTrue($result['public']['upload_files_drop']);
	}

	public function testLinkNoPublicUpload(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_links', 'yes', 'yes'],
			['core', 'shareapi_allow_public_upload', 'yes', 'no'],
			['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
		];
		$result = $this->getResults($map);
		$this->assertFalse($result['public']['upload']);
		$this->assertFalse($result['public']['upload_files_drop']);
	}

	public function testNoGroupSharing(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_group_sharing', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertFalse($result['group_sharing']);
	}

	public function testGroupSharing(): void {
		$map = [
			['core', 'shareapi_enabled', 'yes', 'yes'],
			['core', 'shareapi_allow_group_sharing', 'yes', 'yes'],
		];
		$result = $this->getResults($map);
		$this->assertTrue($result['group_sharing']);
	}

	public function testFederatedSharingIncoming(): void {
		$map = [
			['files_sharing', 'incoming_server2server_share_enabled', 'yes', 'yes'],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('federation', $result);
		$this->assertTrue($result['federation']['incoming']);
	}

	public function testFederatedSharingNoIncoming(): void {
		$map = [
			['files_sharing', 'incoming_server2server_share_enabled', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('federation', $result);
		$this->assertFalse($result['federation']['incoming']);
	}

	public function testFederatedSharingOutgoing(): void {
		$map = [
			['files_sharing', 'outgoing_server2server_share_enabled', 'yes', 'yes'],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('federation', $result);
		$this->assertTrue($result['federation']['outgoing']);
	}

	public function testFederatedSharingNoOutgoing(): void {
		$map = [
			['files_sharing', 'outgoing_server2server_share_enabled', 'yes', 'no'],
		];
		$result = $this->getResults($map);
		$this->assertArrayHasKey('federation', $result);
		$this->assertFalse($result['federation']['outgoing']);
	}

	public function testFederatedSharingExpirationDate(): void {
		$result = $this->getResults([]);
		$this->assertArrayHasKey('federation', $result);
		$this->assertEquals(['enabled' => true], $result['federation']['expire_date']);
		$this->assertEquals(['enabled' => true], $result['federation']['expire_date_supported']);
	}
}

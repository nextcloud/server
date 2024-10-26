<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API\Tests\unit;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Provisioning_API\Capabilities;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Capabilities test for provisioning API.
 *
 * Note: group DB needed because of usage of overwriteService()
 *
 * @package OCA\Provisioning_API\Tests
 * @group DB
 */
class CapabilitiesTest extends TestCase {

	/** @var Capabilities */
	protected $capabilities;

	/** @var IAppManager|MockObject */
	protected $appManager;

	public function setUp(): void {
		parent::setUp();
		$this->appManager = $this->createMock(IAppManager::class);
		$this->capabilities = new Capabilities($this->appManager);

		$this->appManager->expects($this->once())
			->method('getAppVersion')
			->with('provisioning_api')
			->willReturn('1.12');
	}

	public function getCapabilitiesProvider() {
		return [
			[true, false, false, true, false],
			[true, true, false, true, false],
			[true, true, true, true, true],
			[false, false, false, false, false],
			[false, true, false, false, false],
			[false, true, true, false, true],
		];
	}

	/**
	 * @dataProvider getCapabilitiesProvider
	 */
	public function testGetCapabilities($federationAppEnabled, $federatedFileSharingAppEnabled, $lookupServerEnabled, $expectedFederatedScopeEnabled, $expectedPublishedScopeEnabled): void {
		$this->appManager->expects($this->any())
			->method('isEnabledForUser')
			->will($this->returnValueMap([
				['federation', null, $federationAppEnabled],
				['federatedfilesharing', null, $federatedFileSharingAppEnabled],
			]));

		$federatedShareProvider = $this->createMock(FederatedShareProvider::class);
		$this->overwriteService(FederatedShareProvider::class, $federatedShareProvider);

		$federatedShareProvider->expects($this->any())
			->method('isLookupServerUploadEnabled')
			->willReturn($lookupServerEnabled);

		$expected = [
			'provisioning_api' => [
				'version' => '1.12',
				'AccountPropertyScopesVersion' => 2,
				'AccountPropertyScopesFederatedEnabled' => $expectedFederatedScopeEnabled,
				'AccountPropertyScopesPublishedEnabled' => $expectedPublishedScopeEnabled,
			],
		];
		$this->assertSame($expected, $this->capabilities->getCapabilities());
	}
}

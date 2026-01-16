<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API\Tests;

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
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class CapabilitiesTest extends TestCase {

	protected IAppManager&MockObject $appManager;
	protected Capabilities $capabilities;

	public function setUp(): void {
		parent::setUp();
		$this->appManager = $this->createMock(IAppManager::class);
		$this->capabilities = new Capabilities($this->appManager);

		$this->appManager->expects($this->once())
			->method('getAppVersion')
			->with('provisioning_api')
			->willReturn('1.12');
	}

	public static function getCapabilitiesProvider(): array {
		return [
			[true, false, false, true, false],
			[true, true, false, true, false],
			[true, true, true, true, true],
			[false, false, false, false, false],
			[false, true, false, false, false],
			[false, true, true, false, true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'getCapabilitiesProvider')]
	public function testGetCapabilities(bool $federationAppEnabled, bool $federatedFileSharingAppEnabled, bool $lookupServerEnabled, bool $expectedFederatedScopeEnabled, bool $expectedPublishedScopeEnabled): void {
		$this->appManager->expects($this->any())
			->method('isEnabledForUser')
			->willReturnMap([
				['federation', null, $federationAppEnabled],
				['federatedfilesharing', null, $federatedFileSharingAppEnabled],
			]);

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

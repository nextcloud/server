<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\User;

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\OfflineUser;
use OCP\Config\IUserConfig;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class OfflineUserTest extends TestCase {
	protected UserMapping&MockObject $mapping;
	protected string $uid;
	protected IUserConfig&MockObject $userConfig;
	protected IManager&MockObject $shareManager;
	protected OfflineUser $offlineUser;

	public function setUp(): void {
		$this->uid = 'deborah';
		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->mapping = $this->createMock(UserMapping::class);
		$this->shareManager = $this->createMock(IManager::class);

		$this->offlineUser = new OfflineUser(
			$this->uid,
			$this->userConfig,
			$this->mapping,
			$this->shareManager
		);
	}

	public static function shareOwnerProvider(): array {
		return [
			[[], false],
			[[IShare::TYPE_USER], true],
			[[IShare::TYPE_GROUP, IShare::TYPE_LINK], true],
			[[IShare::TYPE_EMAIL, IShare::TYPE_REMOTE, IShare::TYPE_CIRCLE], true],
			[[IShare::TYPE_GUEST, IShare::TYPE_REMOTE_GROUP, IShare::TYPE_ROOM], true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'shareOwnerProvider')]
	public function testHasActiveShares(array $existingShareTypes, bool $expected): void {
		$shareMock = $this->createMock(IShare::class);

		$this->shareManager->expects($this->atLeastOnce())
			->method('getSharesBy')
			->willReturnCallback(function (string $uid, int $shareType) use ($existingShareTypes, $shareMock) {
				if (in_array($shareType, $existingShareTypes)) {
					return [$shareMock];
				}
				return [];
			});

		$this->assertSame($expected, $this->offlineUser->getHasActiveShares());
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\User;

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\OfflineUser;
use OCP\IConfig;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Test\TestCase;

class OfflineUserTest extends TestCase {

	/** @var OfflineUser */
	protected $offlineUser;
	/** @var UserMapping|\PHPUnit\Framework\MockObject\MockObject */
	protected $mapping;
	/** @var string */
	protected $uid;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $shareManager;

	public function setUp(): void {
		$this->uid = 'deborah';
		$this->config = $this->createMock(IConfig::class);
		$this->mapping = $this->createMock(UserMapping::class);
		$this->shareManager = $this->createMock(IManager::class);

		$this->offlineUser = new OfflineUser(
			$this->uid,
			$this->config,
			$this->mapping,
			$this->shareManager
		);
	}

	public function shareOwnerProvider(): array {
		return [
			[[], false],
			[[IShare::TYPE_USER], true],
			[[IShare::TYPE_GROUP, IShare::TYPE_LINK], true],
			[[IShare::TYPE_EMAIL, IShare::TYPE_REMOTE, IShare::TYPE_CIRCLE], true],
			[[IShare::TYPE_GUEST, IShare::TYPE_REMOTE_GROUP, IShare::TYPE_ROOM], true],
		];
	}

	/**
	 * @dataProvider shareOwnerProvider
	 */
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

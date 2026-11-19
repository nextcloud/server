<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CardDAV;

use OCA\DAV\CardDAV\SystemAddressBookVisibility;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SystemAddressBookVisibilityTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IGroupManager&MockObject $groupManager;
	private SystemAddressBookVisibility $visibility;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->groupManager->method('getUserGroupIds')
			->willReturnCallback(fn (IUser $user): array => match ($user->getUID()) {
				'viewer' => ['g1', 'g2'],
				'groupmate' => ['g2'],
				default => ['g3'],
			});

		$this->visibility = new SystemAddressBookVisibility($this->appConfig, $this->groupManager);
	}

	private function createUser(string $uid, string $backend = 'Database'): IUser&MockObject {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$user->method('getBackendClassName')->willReturn($backend);
		return $user;
	}

	public static function visibilityData(): array {
		return [
			'not exposed' => [false, 'yes', 'no', 'no', 'viewer', 'Database', 'other', 'Database', false],
			'not exposed, own card' => [false, 'yes', 'no', 'no', 'viewer', 'Database', 'viewer', 'Database', false],
			'full enumeration' => [true, 'yes', 'no', 'no', 'viewer', 'Database', 'other', 'Database', true],
			'enumeration disabled' => [true, 'no', 'no', 'no', 'viewer', 'Database', 'other', 'Database', false],
			'enumeration disabled, own card' => [true, 'no', 'no', 'no', 'viewer', 'Database', 'viewer', 'Database', true],
			'phone only' => [true, 'yes', 'no', 'yes', 'viewer', 'Database', 'other', 'Database', false],
			'group, shared group' => [true, 'yes', 'yes', 'no', 'viewer', 'Database', 'groupmate', 'Database', true],
			'group, no shared group' => [true, 'yes', 'yes', 'no', 'viewer', 'Database', 'other', 'Database', false],
			'group and phone, shared group' => [true, 'yes', 'yes', 'yes', 'viewer', 'Database', 'groupmate', 'Database', true],
			'guest viewer' => [true, 'yes', 'no', 'no', 'viewer', 'Guests', 'other', 'Database', false],
			'guest viewer, own card' => [true, 'yes', 'no', 'no', 'viewer', 'Guests', 'viewer', 'Guests', true],
			'guest target' => [true, 'yes', 'no', 'no', 'viewer', 'Database', 'groupmate', 'Guests', false],
		];
	}

	#[DataProvider('visibilityData')]
	public function testIsUserCardVisible(
		bool $exposed,
		string $enumeration,
		string $restrictToGroup,
		string $restrictToPhone,
		string $viewerUid,
		string $viewerBackend,
		string $targetUid,
		string $targetBackend,
		bool $expected,
	): void {
		$this->appConfig->method('getValueBool')
			->with('dav', 'system_addressbook_exposed')
			->willReturn($exposed);
		$this->appConfig->method('getValueString')
			->willReturnMap([
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', false, $enumeration],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', false, $restrictToGroup],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', false, $restrictToPhone],
			]);

		$this->assertSame($expected, $this->visibility->isUserCardVisible(
			$this->createUser($viewerUid, $viewerBackend),
			$this->createUser($targetUid, $targetBackend),
		));
	}
}

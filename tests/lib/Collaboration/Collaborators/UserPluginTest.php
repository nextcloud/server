<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Collaborators;

use OC\Collaboration\Collaborators\SearchResult;
use OC\Collaboration\Collaborators\UserPlugin;
use OC\Core\AppInfo\ConfigLexicon;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IFunctionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IShare;
use OCP\Teams\ITeamManager;
use OCP\UserStatus\IManager as IUserStatusManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserPluginTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IUserManager&MockObject $userManager;
	private IDBConnection&MockObject $connection;
	private UserPlugin $plugin;
	/** @var array<string, string> */
	private array $appConfigValues = [
		'shareapi_restrict_user_enumeration_full_match' => 'no',
	];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->method('getValueString')
			->willReturnCallback(fn (string $app, string $key, string $default): string => $this->appConfigValues[$key] ?? $default);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->connection = $this->createMock(IDBConnection::class);

		$currentUser = $this->createMock(IUser::class);
		$currentUser->method('getUID')
			->willReturn('current');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')
			->willReturn($currentUser);

		$this->plugin = new UserPlugin(
			$this->appConfig,
			$this->userManager,
			$this->createMock(IGroupManager::class),
			$this->createMock(ITeamManager::class),
			$userSession,
			$this->createMock(IUserStatusManager::class),
			$this->connection,
		);
	}

	private function createAlice(): IUser&MockObject {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('alice');
		$user->method('getDisplayName')
			->willReturn('Alice');
		$user->method('getSystemEMailAddress')
			->willReturn('alice@example.com');
		$user->method('isEnabled')
			->willReturn(true);
		return $user;
	}

	public static function dataUniqueDisplayName(): array {
		return [
			'email address shown' => [true, 'alice@example.com'],
			'email address hidden' => [false, 'alice'],
		];
	}

	#[DataProvider('dataUniqueDisplayName')]
	public function testUniqueDisplayName(bool $showAccountEMail, string $expected): void {
		$this->appConfig->method('getValueBool')
			->with('core', ConfigLexicon::SHARE_SHOW_ACCOUNT_EMAIL)
			->willReturn($showAccountEMail);

		$this->userManager->method('searchDisplayName')
			->with('ali', 2, 0)
			->willReturn([$this->createAlice()]);

		$searchResult = new SearchResult();
		$this->plugin->search('ali', 2, 0, $searchResult);

		$this->assertEquals([
			'exact' => ['users' => []],
			'users' => [[
				'label' => 'Alice',
				'subline' => '',
				'icon' => 'icon-user',
				'value' => [
					'shareType' => IShare::TYPE_USER,
					'shareWith' => 'alice',
				],
				'shareWithDisplayNameUnique' => $expected,
				'status' => [],
			]],
		], $searchResult->asArray());
	}

	public static function dataFullMatchOnAdditionalEMailAddress(): array {
		return [
			'email address shown' => [true, 'Alice (second@example.com)', 'second@example.com'],
			'email address hidden' => [false, 'Alice', 'alice'],
		];
	}

	#[DataProvider('dataFullMatchOnAdditionalEMailAddress')]
	public function testFullMatchOnAdditionalEMailAddress(bool $showAccountEMail, string $expectedLabel, string $expectedUniqueDisplayName): void {
		$this->appConfigValues = [
			'shareapi_allow_share_dialog_user_enumeration' => 'no',
			'shareapi_restrict_user_enumeration_full_match' => 'yes',
		];
		$this->appConfig->method('getValueBool')
			->with('core', ConfigLexicon::SHARE_SHOW_ACCOUNT_EMAIL)
			->willReturn($showAccountEMail);

		$this->userManager->method('searchDisplayName')
			->willReturn([]);
		$this->userManager->method('get')
			->willReturnMap([
				['second@example.com', null],
				['alice', $this->createAlice()],
			]);

		$result = $this->createMock(IResult::class);
		$result->method('fetchAssociative')
			->willReturnOnConsecutiveCalls(
				['uid' => 'alice', 'value' => 'second@example.com', 'name' => 'additional_mail'],
				false,
			);
		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$queryBuilder->method('select')->willReturnSelf();
		$queryBuilder->method('from')->willReturnSelf();
		$queryBuilder->method('where')->willReturnSelf();
		$queryBuilder->method('andWhere')->willReturnSelf();
		$queryBuilder->method('expr')
			->willReturn($this->createMock(IExpressionBuilder::class));
		$queryBuilder->method('func')
			->willReturn($this->createMock(IFunctionBuilder::class));
		$queryBuilder->method('executeQuery')
			->willReturn($result);
		$this->connection->method('getQueryBuilder')
			->willReturn($queryBuilder);

		$searchResult = new SearchResult();
		$this->plugin->search('second@example.com', 2, 0, $searchResult);

		$this->assertEquals([
			'exact' => ['users' => [[
				'label' => $expectedLabel,
				'subline' => '',
				'icon' => 'icon-user',
				'value' => [
					'shareType' => IShare::TYPE_USER,
					'shareWith' => 'alice',
				],
				'shareWithDisplayNameUnique' => $expectedUniqueDisplayName,
				'status' => [],
			]]],
			'users' => [],
		], $searchResult->asArray());
	}
}

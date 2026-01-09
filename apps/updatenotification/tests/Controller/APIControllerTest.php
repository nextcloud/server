<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UpdateNotification\Tests\Controller;

use OC\App\AppStore\Fetcher\AppFetcher;
use OCA\UpdateNotification\AppInfo\Application;
use OCA\UpdateNotification\Controller\APIController;
use OCA\UpdateNotification\Manager;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class APIControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private IConfig&MockObject $config;
	private IAppManager&MockObject $appManager;
	private AppFetcher&MockObject $appFetcher;
	private IFactory&MockObject $l10nFactory;
	private IUserSession&MockObject $userSession;
	private Manager&MockObject $manager;

	private APIController $apiController;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->appFetcher = $this->createMock(AppFetcher::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->manager = $this->createMock(Manager::class);

		$this->apiController = new APIController(
			Application::APP_NAME,
			$this->request,
			$this->config,
			$this->appManager,
			$this->appFetcher,
			$this->l10nFactory,
			$this->userSession,
			$this->manager,
		);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGetAppChangelog')]
	public function testGetAppChangelogEntry(
		array $params,
		bool $hasChanges,
		array $appInfo,
		array $expected,
	): void {
		$this->appManager->method('getAppInfo')
			->with('the-app')
			->willReturn($appInfo);
		$this->appManager->method('getAppVersion')
			->with('the-app')
			->willReturn($appInfo['version']);
		$this->manager->method('getChangelog')
			->with('the-app', self::anything())
			->willReturnCallback(fn ($app, $version) => $hasChanges ? "$app v$version" : null);

		$result = $this->apiController->getAppChangelogEntry(...$params);
		$this->assertEquals($result->getStatus(), $expected['status']);
		$this->assertEquals($result->getData(), $expected['data']);
	}

	public static function dataGetAppChangelog(): array {
		return [
			'no changes found' => [
				['the-app', null],
				false,
				[
					'name' => 'Localized name',
					'version' => '1.0.0',
				],
				[
					'status' => Http::STATUS_NOT_FOUND,
					'data' => [],
				]
			],
			'changes with version parameter' => [
				['the-app', '1.0.0'],
				true,
				[
					'name' => 'Localized name',
					'version' => '1.2.0', // installed version
				],
				[
					'status' => Http::STATUS_OK,
					'data' => [
						'appName' => 'Localized name',
						'content' => 'the-app v1.0.0',
						'version' => '1.0.0',
					],
				]
			],
			'changes without version parameter' => [
				['the-app', null],
				true,
				[
					'name' => 'Localized name',
					'version' => '1.2.0',
				],
				[
					'status' => Http::STATUS_OK,
					'data' => [
						'appName' => 'Localized name',
						'content' => 'the-app v1.2.0',
						'version' => '1.2.0',
					],
				]
			],
			'changes of pre-release version' => [
				['the-app', null],
				true,
				[
					'name' => 'Localized name',
					'version' => '1.2.0-alpha.1',
				],
				[
					'status' => Http::STATUS_OK,
					'data' => [
						'appName' => 'Localized name',
						'content' => 'the-app v1.2.0',
						'version' => '1.2.0-alpha.1',
					],
				]
			],
			'changes of pre-release version as parameter' => [
				['the-app', '1.2.0-alpha.2'],
				true,
				[
					'name' => 'Localized name',
					'version' => '1.2.0-beta.3',
				],
				[
					'status' => Http::STATUS_OK,
					'data' => [
						'appName' => 'Localized name',
						'content' => 'the-app v1.2.0',
						'version' => '1.2.0-alpha.2',
					],
				]
			],
		];
	}
}

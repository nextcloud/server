<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Listener;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_Sharing\External\Manager as ExternalManager;
use OCA\Files_Sharing\Listener\LoadAdditionalListener;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Util;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class LoadAdditionalListenerTest extends TestCase {
	protected LoggerInterface&MockObject $logger;
	protected LoadAdditionalScriptsEvent&MockObject $event;
	protected IManager&MockObject $shareManager;
	protected IFactory&MockObject $factory;
	protected IInitialState&MockObject $initialState;
	protected IUserSession&MockObject $userSession;
	protected ExternalManager&MockObject $externalManager;
	protected IConfig&MockObject $config;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->event = $this->createMock(LoadAdditionalScriptsEvent::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->factory = $this->createMock(IFactory::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->externalManager = $this->createMock(ExternalManager::class);
		$this->config = $this->createMock(IConfig::class);

		/* Empty static array to avoid inter-test conflicts */
		\OC_Util::$styles = [];
		self::invokePrivate(Util::class, 'scripts', [[]]);
		self::invokePrivate(Util::class, 'scriptDeps', [[]]);
		self::invokePrivate(Util::class, 'scriptsInit', [[]]);
	}

	protected function tearDown(): void {
		parent::tearDown();

		\OC_Util::$styles = [];
		self::invokePrivate(Util::class, 'scripts', [[]]);
		self::invokePrivate(Util::class, 'scriptDeps', [[]]);
		self::invokePrivate(Util::class, 'scriptsInit', [[]]);
	}

	public function testHandleIgnoresNonMatchingEvent(): void {
		$listener = new LoadAdditionalListener(
			$this->initialState,
			$this->config,
			$this->shareManager,
			$this->userSession,
			$this->externalManager,
		);
		$event = $this->createMock(Event::class);

		// Should not throw or call anything
		$listener->handle($event);

		$this->assertTrue(true); // No exception means pass
	}

	public function testHandleWithLoadAdditionalScriptsEvent(): void {
		$listener = new LoadAdditionalListener(
			$this->initialState,
			$this->config,
			$this->shareManager,
			$this->userSession,
			$this->externalManager,
		);

		$this->shareManager->method('shareApiEnabled')->willReturn(false);
		$this->factory->method('findLanguage')->willReturn('language_mock');
		$this->userSession->method('getUser')->willReturn(null);

		$this->overwriteService(IFactory::class, $this->factory);

		$scriptsBefore = Util::getScripts();
		$this->assertNotContains('files_sharing/l10n/language_mock', $scriptsBefore);
		$this->assertNotContains('files_sharing/js/additionalScripts', $scriptsBefore);
		$this->assertNotContains('files_sharing/js/init', $scriptsBefore);
		$this->assertNotContains('files_sharing/css/icons', \OC_Util::$styles);

		// Util static methods can't be easily mocked, so just ensure no exceptions
		$listener->handle($this->event);

		// assert array $scripts contains the expected scripts
		$scriptsAfter = Util::getScripts();
		$this->assertContains('files_sharing/l10n/language_mock', $scriptsAfter);
		$this->assertContains('files_sharing/js/additionalScripts', $scriptsAfter);
		$this->assertNotContains('files_sharing/js/init', $scriptsAfter);

		$this->assertContains('files_sharing/css/icons', \OC_Util::$styles);
	}

	public function testHandleWithLoadAdditionalScriptsEventWithShareApiEnabled(): void {
		$listener = new LoadAdditionalListener(
			$this->initialState,
			$this->config,
			$this->shareManager,
			$this->userSession,
			$this->externalManager,
		);

		$this->shareManager->method('shareApiEnabled')->willReturn(true);
		$this->userSession->method('getUser')->willReturn(null);

		$this->overwriteService(IFactory::class, $this->factory);

		$scriptsBefore = Util::getScripts();
		$this->assertNotContains('files_sharing/js/init', $scriptsBefore);

		// Util static methods can't be easily mocked, so just ensure no exceptions
		$listener->handle($this->event);

		$scriptsAfter = Util::getScripts();

		// assert array $scripts contains the expected scripts
		$this->assertContains('files_sharing/js/init', $scriptsAfter);
	}

	public function testProvideInitialStatesWithPendingInternalShares(): void {
		$listener = new LoadAdditionalListener(
			$this->initialState,
			$this->config,
			$this->shareManager,
			$this->userSession,
			$this->externalManager,
		);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('testuser');
		$this->userSession->method('getUser')->willReturn($user);

		$share = $this->createMock(IShare::class);
		$share->method('getStatus')->willReturn(IShare::STATUS_PENDING);

		$this->shareManager->method('shareApiEnabled')->willReturn(true);
		$this->shareManager->method('getSharedWith')
			->willReturnCallback(function (string $userId, int $shareType) use ($share) {
				if ($shareType === IShare::TYPE_USER) {
					return [$share];
				}
				return [];
			});

		$this->externalManager->method('getOpenShares')->willReturn([]);
		$this->config->method('getSystemValueBool')->with('sharing.enable_share_accept')->willReturn(true);

		$expectedCalls = [
			['sharing.enable_share_accept', true],
			['has_pending_shares', true],
		];
		$this->initialState->expects($this->exactly(2))
			->method('provideInitialState')
			->willReturnCallback(function (string $key, $data) use (&$expectedCalls): void {
				$this->assertSame(array_shift($expectedCalls), [$key, $data]);
			});

		$this->overwriteService(IFactory::class, $this->factory);

		$listener->handle($this->event);
	}

	public function testProvideInitialStatesWithPendingRemoteShares(): void {
		$listener = new LoadAdditionalListener(
			$this->initialState,
			$this->config,
			$this->shareManager,
			$this->userSession,
			$this->externalManager,
		);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('testuser');
		$this->userSession->method('getUser')->willReturn($user);

		$this->shareManager->method('shareApiEnabled')->willReturn(true);
		$this->shareManager->method('getSharedWith')->willReturn([]);

		$this->externalManager->method('getOpenShares')->willReturn([['id' => 1, 'remote' => 'example.com']]);
		$this->config->method('getSystemValueBool')->with('sharing.enable_share_accept')->willReturn(true);

		$expectedCalls = [
			['sharing.enable_share_accept', true],
			['has_pending_shares', true],
		];
		$this->initialState->expects($this->exactly(2))
			->method('provideInitialState')
			->willReturnCallback(function (string $key, $data) use (&$expectedCalls): void {
				$this->assertSame(array_shift($expectedCalls), [$key, $data]);
			});

		$this->overwriteService(IFactory::class, $this->factory);

		$listener->handle($this->event);
	}

	public function testProvideInitialStatesWithNoPendingShares(): void {
		$listener = new LoadAdditionalListener(
			$this->initialState,
			$this->config,
			$this->shareManager,
			$this->userSession,
			$this->externalManager,
		);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('testuser');
		$this->userSession->method('getUser')->willReturn($user);

		$this->shareManager->method('shareApiEnabled')->willReturn(true);
		$this->shareManager->method('getSharedWith')->willReturn([]);

		$this->externalManager->method('getOpenShares')->willReturn([]);
		$this->config->method('getSystemValueBool')->with('sharing.enable_share_accept')->willReturn(false);

		$expectedCalls = [
			['sharing.enable_share_accept', false],
			['has_pending_shares', false],
		];
		$this->initialState->expects($this->exactly(2))
			->method('provideInitialState')
			->willReturnCallback(function (string $key, $data) use (&$expectedCalls): void {
				$this->assertSame(array_shift($expectedCalls), [$key, $data]);
			});

		$this->overwriteService(IFactory::class, $this->factory);

		$listener->handle($this->event);
	}

	public function testProvideInitialStatesWithNoUser(): void {
		$listener = new LoadAdditionalListener(
			$this->initialState,
			$this->config,
			$this->shareManager,
			$this->userSession,
			$this->externalManager,
		);

		$this->userSession->method('getUser')->willReturn(null);

		$this->initialState->expects($this->never())
			->method('provideInitialState');

		$this->shareManager->method('shareApiEnabled')->willReturn(true);

		$this->overwriteService(IFactory::class, $this->factory);

		$listener->handle($this->event);
	}
}

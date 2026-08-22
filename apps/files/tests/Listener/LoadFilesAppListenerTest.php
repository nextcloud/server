<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Listener;

use OC\Files\FilenameValidator;
use OCA\Files\Event\LoadFilesApp;
use OCA\Files\Listener\LoadFilesAppListener;
use OCA\Files\Service\UserConfig;
use OCA\Files\Service\ViewConfig;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Template\ITemplateManager;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class LoadFilesAppListenerTest extends TestCase {
	private IEventDispatcher&MockObject $eventDispatcher;
	private IInitialState&MockObject $initialState;
	private IConfig&MockObject $config;
	private IUserSession&MockObject $userSession;
	private UserConfig&MockObject $userConfig;
	private ViewConfig&MockObject $viewConfig;
	private FilenameValidator&MockObject $filenameValidator;
	private IRegistry&MockObject $twoFactorRegistry;
	private IAppConfig&MockObject $appConfig;
	private ITemplateManager&MockObject $templateManager;
	private IUser&MockObject $user;

	private LoadFilesAppListener $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userConfig = $this->createMock(UserConfig::class);
		$this->viewConfig = $this->createMock(ViewConfig::class);
		$this->filenameValidator = $this->createMock(FilenameValidator::class);
		$this->twoFactorRegistry = $this->createMock(IRegistry::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->templateManager = $this->createMock(ITemplateManager::class);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('testuser1');
		$this->userSession->method('getUser')->willReturn($this->user);

		$this->config->method('getUserValue')->willReturn('{}');

		$this->listener = new LoadFilesAppListener(
			$this->eventDispatcher,
			$this->initialState,
			$this->config,
			$this->userSession,
			$this->userConfig,
			$this->viewConfig,
			$this->filenameValidator,
			$this->twoFactorRegistry,
			$this->appConfig,
			$this->templateManager,
		);
	}

	public function testIgnoresOtherEvents(): void {
		$this->initialState->expects(self::never())->method('provideInitialState');
		$this->listener->handle($this->createMock(Event::class));
	}

	public function testProvidesInitialStateAndDispatchesLoadEvents(): void {
		$this->twoFactorRegistry->method('getProviderStates')
			->willReturn([
				'totp' => true,
				'backup_codes' => true,
			]);

		$initialStates = [];
		$this->initialState->expects(self::atLeastOnce())
			->method('provideInitialState')
			->willReturnCallback(function ($key, $data) use (&$initialStates): void {
				$initialStates[$key] = $data;
			});

		$this->listener->handle(new LoadFilesApp());

		$this->assertTrue($initialStates['isTwoFactorEnabled'] ?? false);
		$this->assertTrue($initialStates['templates_enabled'] ?? false);
	}
}

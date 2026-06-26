<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Listener;

use OC\InitialStateService;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_Sharing\Listener\LoadAdditionalListener;
use OCP\EventDispatcher\Event;
use OCP\IConfig;
use OCP\L10N\IFactory;
use OCP\Share\IManager;
use OCP\Util;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class LoadAdditionalListenerTest extends TestCase {
	protected LoggerInterface&MockObject $logger;
	protected LoadAdditionalScriptsEvent&MockObject $event;
	protected IManager&MockObject $shareManager;
	protected IFactory&MockObject $factory;
	protected InitialStateService&MockObject $initialStateService;
	protected IConfig&MockObject $config;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->event = $this->createMock(LoadAdditionalScriptsEvent::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->factory = $this->createMock(IFactory::class);
		$this->initialStateService = $this->createMock(InitialStateService::class);
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
		$listener = new LoadAdditionalListener();
		$event = $this->createMock(Event::class);

		// Should not throw or call anything
		$listener->handle($event);

		$this->assertTrue(true); // No exception means pass
	}

	public function testHandleWithLoadAdditionalScriptsEvent(): void {
		$listener = new LoadAdditionalListener();

		$this->shareManager->method('shareApiEnabled')->willReturn(false);
		$this->factory->method('findLanguage')->willReturn('language_mock');
		$this->config->method('getSystemValueBool')->willReturn(true);

		$this->overwriteService(IManager::class, $this->shareManager);
		$this->overwriteService(IFactory::class, $this->factory);
		$this->overwriteService(InitialStateService::class, $this->initialStateService);
		$this->overwriteService(IConfig::class, $this->config);

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
		$listener = new LoadAdditionalListener();

		$this->shareManager->method('shareApiEnabled')->willReturn(true);
		$this->config->method('getSystemValueBool')->willReturn(true);

		$this->overwriteService(IManager::class, $this->shareManager);
		$this->overwriteService(InitialStateService::class, $this->initialStateService);
		$this->overwriteService(IConfig::class, $this->config);
		$this->overwriteService(IFactory::class, $this->factory);

		$scriptsBefore = Util::getScripts();
		$this->assertNotContains('files_sharing/js/init', $scriptsBefore);

		// Util static methods can't be easily mocked, so just ensure no exceptions
		$listener->handle($this->event);

		$scriptsAfter = Util::getScripts();

		// assert array $scripts contains the expected scripts
		$this->assertContains('files_sharing/js/init', $scriptsAfter);
	}
}

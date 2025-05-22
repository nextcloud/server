<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
declare(strict_types=1);

namespace OCA\Files_Sharing\Tests\Listener;

use OC\AppScriptSort;
use OC\InitialStateService;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_Sharing\Listener\LoadAdditionalListener;
use OCP\EventDispatcher\Event;
use OCP\IConfig;
use OCP\L10N\IFactory;
use OCP\Share\IManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoadAdditionalListenerTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->getMockBuilder(LoggerInterface::class)
			->disableOriginalConstructor()
			->getMock();

		// Mock global server container
		$this->server = $this->createMock(\OC\Server::class);
		\OC::$server = $this->server;
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

		// Mock dependencies
		$event = $this->createMock(LoadAdditionalScriptsEvent::class);

		$shareManager = $this->createMock(IManager::class);
		$shareManager->method('shareApiEnabled')->willReturn(false);

		$factory = $this->createMock(IFactory::class);
		$factory->method('findLanguage')->willReturn('en');

		$initialState = $this->createMock(InitialStateService::class);
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')->willReturn(true);

		$scriptSort = new AppScriptSort($this->logger);

		$this->server->method('get')
			->willReturnMap([
				[IManager::class, $shareManager],
				[InitialStateService::class, $initialState],
				[IConfig::class, $config],
				[IFactory::class, $factory],
				[AppScriptSort::class, $scriptSort],
			]);

		$scripts = \OCP\Util::getScripts();
		$this->assertNotContains('files_sharing/l10n/en', $scripts);
		$this->assertNotContains('files_sharing/js/additionalScripts', $scripts);
		$this->assertNotContains('files_sharing/js/init', $scripts);
		$this->assertNotContains('files_sharing/css/icons', \OC_Util::$styles);

		// Util static methods can't be easily mocked, so just ensure no exceptions
		$listener->handle($event);

		$scripts = \OCP\Util::getScripts();

		// assert array $scripts contains the expected scripts
		$this->assertContains('files_sharing/l10n/en', $scripts);
		$this->assertContains('files_sharing/js/additionalScripts', $scripts);
		$this->assertNotContains('files_sharing/js/init', $scripts);

		$this->assertContains('files_sharing/css/icons', \OC_Util::$styles);

		$this->assertTrue(true);
	}

	public function testHandleWithLoadAdditionalScriptsEventWithShareApiEnabled(): void {
		$listener = new LoadAdditionalListener();

		// Mock dependencies
		$event = $this->createMock(LoadAdditionalScriptsEvent::class);

		$shareManager = $this->createMock(IManager::class);
		$shareManager->method('shareApiEnabled')->willReturn(true);

		$factory = $this->createMock(IFactory::class);
		$factory->method('findLanguage')->willReturn('en');

		$initialState = $this->createMock(InitialStateService::class);
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')->willReturn(true);

		$scriptSort = new AppScriptSort($this->logger);

		$this->server->method('get')
			->willReturnMap([
				[IManager::class, $shareManager],
				[InitialStateService::class, $initialState],
				[IConfig::class, $config],
				[IFactory::class, $factory],
				[AppScriptSort::class, $scriptSort],
			]);

		$scripts = \OCP\Util::getScripts();
		$this->assertNotContains('files_sharing/js/init', $scripts);

		// Util static methods can't be easily mocked, so just ensure no exceptions
		$listener->handle($event);

		$scripts = \OCP\Util::getScripts();

		// assert array $scripts contains the expected scripts
		$this->assertContains('files_sharing/js/init', $scripts);

		$this->assertTrue(true);
	}
}

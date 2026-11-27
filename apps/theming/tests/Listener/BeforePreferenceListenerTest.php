<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests\Listener;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ConfigLexicon;
use OCA\Theming\Listener\BeforePreferenceListener;
use OCP\App\IAppManager;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\EventDispatcher\GenericEvent;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BeforePreferenceListenerTest extends TestCase {
	private IAppManager&MockObject $appManager;
	private IConfig&MockObject $config;
	private BeforePreferenceListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->config = $this->createMock(IConfig::class);

		$this->listener = new BeforePreferenceListener(
			$this->appManager,
			$this->config,
		);
	}

	public function testHandleInvalidEvent(): void {
		$event = new GenericEvent();
		$this->listener->handle($event);
		$this->assertTrue(true);
	}

	public function testHandleThemingValuesUnallowedKey(): void {
		$event = new BeforePreferenceSetEvent('user1', Application::APP_ID, 'unallowed_key', 'value');
		$this->listener->handle($event);
		$this->assertFalse($event->isValid());
	}

	public function testHandleThemingValuesPrimaryColorValid(): void {
		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user1', Application::APP_ID, 'userCacheBuster', '0')
			->willReturn('2');

		$this->config->expects($this->once())
			->method('setUserValue')
			->with('user1', Application::APP_ID, 'userCacheBuster', '3');

		$event = new BeforePreferenceSetEvent('user1', Application::APP_ID, 'primary_color', '#ff00aa');
		$this->listener->handle($event);

		$this->assertTrue($event->isValid());
	}

	public function testHandleThemingValuesPrimaryColorInvalid(): void {
		$this->config->expects($this->never())
			->method('setUserValue');

		$event = new BeforePreferenceSetEvent('user1', Application::APP_ID, 'primary_color', 'invalid_color');
		$this->listener->handle($event);

		$this->assertFalse($event->isValid());
	}

	public function testHandleThemingValuesPrimaryColorDeleted(): void {
		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user1', Application::APP_ID, 'userCacheBuster', '0')
			->willReturn('5');

		$this->config->expects($this->once())
			->method('setUserValue')
			->with('user1', Application::APP_ID, 'userCacheBuster', '6');

		$event = new BeforePreferenceDeletedEvent('user1', Application::APP_ID, 'primary_color');
		$this->listener->handle($event);

		$this->assertTrue($event->isValid());
	}

	public function testHandleThemingValuesForceEnableBlurFilter(): void {
		$this->config->expects($this->never())
			->method('setUserValue');

		$event = new BeforePreferenceSetEvent('user1', Application::APP_ID, 'force_enable_blur_filter', 'yes');
		$this->listener->handle($event);

		$this->assertTrue($event->isValid());
	}

	public function testHandleThemingValuesShortcutsDisabled(): void {
		$this->config->expects($this->never())
			->method('setUserValue');

		$event = new BeforePreferenceSetEvent('user1', Application::APP_ID, 'shortcuts_disabled', 'yes');
		$this->listener->handle($event);

		$this->assertTrue($event->isValid());
	}

	public function testHandleThemingValuesToastTimeout(): void {
		$this->config->expects($this->never())
			->method('setUserValue');

		$event = new BeforePreferenceSetEvent('user1', Application::APP_ID, ConfigLexicon::TOAST_TIMEOUT, '7000');
		$this->listener->handle($event);

		$this->assertTrue($event->isValid());
	}

	public function testHandleThemingValuesOtherKeyDeleted(): void {
		$this->config->expects($this->never())
			->method('setUserValue');

		$event = new BeforePreferenceDeletedEvent('user1', Application::APP_ID, 'shortcuts_disabled');
		$this->listener->handle($event);

		$this->assertTrue($event->isValid());
	}
}

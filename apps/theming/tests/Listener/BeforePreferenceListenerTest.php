<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests\Listener;

use OCA\Theming\Listener\BeforePreferenceListener;
use OCP\App\IAppManager;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BeforePreferenceListenerTest extends TestCase {
	private IAppManager&MockObject $appManager;
	private BeforePreferenceListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->listener = new BeforePreferenceListener($this->appManager);
	}

	private function handleSet(string $appId, string $key, string $value): bool {
		$event = new BeforePreferenceSetEvent('user', $appId, $key, $value);
		$this->listener->handle($event);
		return $event->isValid();
	}

	private function handleDelete(string $appId, string $key): bool {
		$event = new BeforePreferenceDeletedEvent('user', $appId, $key);
		$this->listener->handle($event);
		return $event->isValid();
	}

	public static function dataPinnedApps(): array {
		return [
			'empty list' => ['[]', true],
			'list of entry ids' => ['["files","mail"]', true],
			'single entry' => ['["files"]', true],
			'not json' => ['files,mail', false],
			'json object instead of a list' => ['{"files":0}', false],
			'sparse array is not a list' => ['{"1":"files"}', false],
			'non-string member' => ['["files",42]', false],
			'nested member' => ['["files",["mail"]]', false],
			'null member' => ['["files",null]', false],
			'scalar json' => ['"files"', false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataPinnedApps')]
	public function testValidatePinnedApps(string $value, bool $expected): void {
		$this->assertSame($expected, $this->handleSet('core', 'apps_pinned', $value));
	}

	public function testPinnedAppsAreNotValidatedAgainstEnabledApps(): void {
		// Unlike apporder, a pinned id may reference an entry that is not an
		// app id (e.g. a settings entry), so the app manager is not consulted.
		$this->appManager->expects($this->never())->method('isEnabledForUser');

		$this->assertTrue($this->handleSet('core', 'apps_pinned', '["files","settings"]'));
	}

	public function testDeletingPinnedAppsIsAllowed(): void {
		$this->assertTrue($this->handleDelete('core', 'apps_pinned'));
	}

	public function testUnknownCoreKeyStaysRejected(): void {
		$this->assertFalse($this->handleSet('core', 'something_else', '["files"]'));
		$this->assertFalse($this->handleDelete('core', 'something_else'));
	}

	public function testAppOrderStillValidatesAgainstEnabledApps(): void {
		$this->appManager->method('isEnabledForUser')
			->willReturnCallback(static fn (string $app): bool => $app === 'files');

		$this->assertTrue($this->handleSet('core', 'apporder', '{"files":{"order":1,"app":"files"}}'));
		$this->assertFalse($this->handleSet('core', 'apporder', '{"mail":{"order":1,"app":"mail"}}'));
	}
}

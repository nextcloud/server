<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Tests\Listener;

use OCA\Viewer\Listener\SettingsListener;
use OCA\Viewer\Service\UserConfig;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IUser;
use OCP\IUserSession;
use Test\TestCase;

class SettingsListenerTest extends TestCase {
	/** @var array<string, mixed> */
	private array $provided = [];
	private SettingsListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->getAutoMock(IInitialState::class)->method('provideInitialState')
			->willReturnCallback(function (string $key, mixed $data): void {
				$this->provided[$key] = $data;
			});
		$this->getAutoMock(UserConfig::class)->method('getConfigs')
			->willReturn(['slideshow_delay' => 10]);

		$this->listener = $this->createInstanceWithMocks(SettingsListener::class);
	}

	private function signIn(): void {
		$this->getAutoMock(IUserSession::class)->method('getUser')
			->willReturn($this->createStub(IUser::class));
	}

	private function page(string $renderAs = TemplateResponse::RENDER_AS_USER): BeforeTemplateRenderedEvent {
		return new BeforeTemplateRenderedEvent(true, new TemplateResponse('core', 'empty', [], $renderAs));
	}

	public function testHandsTheViewerTheUsersSettings(): void {
		$this->signIn();
		$this->listener->handle($this->page());

		$this->assertSame(['config' => ['slideshow_delay' => 10]], $this->provided);
	}

	public function testHasNoSettingsForAGuest(): void {
		$this->listener->handle($this->page());

		$this->assertSame([], $this->provided);
	}

	public function testLeavesTheErrorPageAlone(): void {
		$this->signIn();
		$this->listener->handle($this->page(TemplateResponse::RENDER_AS_ERROR));

		$this->assertSame([], $this->provided);
	}
}

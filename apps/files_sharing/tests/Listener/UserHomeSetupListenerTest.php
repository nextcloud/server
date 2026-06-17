<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Listener;

use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\Config\ConfigLexicon;
use OCA\Files_Sharing\Listener\UserHomeSetupListener;
use OCA\Files_Sharing\ShareRecipientUpdater;
use OCP\Config\IUserConfig;
use OCP\Files\Events\UserHomeSetupEvent;
use OCP\Files\Mount\IMountPoint;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\Mock\Config\MockUserConfig;
use Test\TestCase;

class UserHomeSetupListenerTest extends TestCase {
	private ShareRecipientUpdater&MockObject $updater;
	private IUserConfig $userConfig;
	private UserHomeSetupListener $listener;
	private IUser $user;

	protected function setUp(): void {
		parent::setUp();

		$this->updater = $this->createMock(ShareRecipientUpdater::class);
		$this->userConfig = new MockUserConfig([]);
		$this->listener = new UserHomeSetupListener($this->updater, $this->userConfig);
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')
			->willReturn('test');
	}

	private function getEvent(): UserHomeSetupEvent {
		$homeMount = $this->createMock(IMountPoint::class);
		return new UserHomeSetupEvent($this->user, $homeMount);
	}

	public function testClearNeedsUpdate(): void {
		$this->userConfig->setValueBool('test', Application::APP_ID, ConfigLexicon::USER_NEEDS_SHARE_REFRESH, true);
		$this->updater->expects($this->once())
			->method('updateForUser');

		$this->listener->handle($this->getEvent());
		$this->assertFalse($this->userConfig->getValueBool('test', Application::APP_ID, ConfigLexicon::USER_NEEDS_SHARE_REFRESH, true));
	}

	public function testNoUpdateIfNotNeeded(): void {
		$this->userConfig->setValueBool('test', Application::APP_ID, ConfigLexicon::USER_NEEDS_SHARE_REFRESH, false);
		$this->updater->expects($this->never())
			->method('updateForUser');

		$this->listener->handle($this->getEvent());
		$this->assertFalse($this->userConfig->getValueBool('test', Application::APP_ID, ConfigLexicon::USER_NEEDS_SHARE_REFRESH, true));
	}
}

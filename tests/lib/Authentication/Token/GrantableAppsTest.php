<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use OC\Authentication\Token\GrantableApps;
use OCP\App\IAppManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use Test\TestCase;

class GrantableAppsTest extends TestCase {
	public function testEnabledAppsWithoutScopedAppsByName(): void {
		$user = $this->createMock(IUser::class);
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);
		$l10nFactory = $this->createMock(IFactory::class);
		$l10nFactory->method('findLanguage')->willReturn('de');
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getEnabledAppsForUser')->with($user)->willReturn(['files', 'spreed', 'dav', 'circles', 'deck', 'files_sharing', 'activity']);
		$appManager->method('getAppInfo')->willReturnCallback(static fn (string $app, bool $path, ?string $lang): ?array => $lang === 'de' ? match ($app) {
			'spreed' => ['name' => 'Talk'],
			'circles' => ['name' => 'Teams'],
			'deck' => ['name' => 'Deck'],
			default => null,
		} : null);
		$appManager->method('getAppIcon')->willReturnCallback(static fn (string $app): ?string => $app === 'deck' ? 'deck.svg' : null);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('imagePath')->with('core', 'places/default-app-icon.svg')->willReturn('default.svg');
		$grantableApps = new GrantableApps($appManager, $userSession, $l10nFactory, $urlGenerator);

		$this->assertSame([
			['id' => 'activity', 'name' => 'activity', 'icon' => 'default.svg'],
			['id' => 'deck', 'name' => 'Deck', 'icon' => 'deck.svg'],
			['id' => 'spreed', 'name' => 'Talk', 'icon' => 'default.svg'],
			['id' => 'circles', 'name' => 'Teams', 'icon' => 'default.svg'],
		], $grantableApps->entries());
		$this->assertSame(['spreed', 'circles', 'deck', 'activity'], $grantableApps->getAppIds());
	}

	public function testNothingWithoutUser(): void {
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn(null);
		$grantableApps = new GrantableApps($this->createMock(IAppManager::class), $userSession, $this->createMock(IFactory::class), $this->createMock(IURLGenerator::class));

		$this->assertSame([], $grantableApps->getAppIds());
		$this->assertSame([], $grantableApps->entries());
	}
}

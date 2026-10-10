<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Lockdown;

use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\TokenScopes;
use OC\Lockdown\LockdownManager;
use OCP\Authentication\Token\IToken;
use OCP\ISession;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class LockdownManagerTest extends TestCase {
	private $sessionCallback;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->sessionCallback = function () {
			return $this->createMock(ISession::class);
		};
	}

	public function testCanAccessFilesystemDisabled(): void {
		$manager = new LockdownManager($this->sessionCallback, new TokenScopes());
		$this->assertTrue($manager->canAccessFilesystem());
	}

	public function testCanAccessFilesystemAllowed(): void {
		$token = new PublicKeyToken();
		$token->setScope([IToken::SCOPE_FILESYSTEM => true]);
		$manager = new LockdownManager($this->sessionCallback, new TokenScopes());
		$manager->setToken($token);
		$this->assertTrue($manager->canAccessFilesystem());
	}

	public function testCanAccessFilesystemNotAllowed(): void {
		$token = new PublicKeyToken();
		$token->setScope([IToken::SCOPE_FILESYSTEM => false]);
		$manager = new LockdownManager($this->sessionCallback, new TokenScopes());
		$manager->setToken($token);
		$this->assertFalse($manager->canAccessFilesystem());
	}

	public static function filesystemAccess(): array {
		return [
			'legacy without filesystem key' => [[IToken::SCOPE_SKIP_PASSWORD_VALIDATION => true], false],
			'files' => [[IToken::SCOPE_FILESYSTEM => false, TokenScopes::KEY => [TokenScopes::FILES_READ]], true],
			'calendar only' => [[IToken::SCOPE_FILESYSTEM => true, TokenScopes::KEY => [TokenScopes::CALENDAR_READ]], false],
			'malformed' => [[IToken::SCOPE_FILESYSTEM => true, TokenScopes::KEY => TokenScopes::FILES_READ], false],
		];
	}

	#[DataProvider('filesystemAccess')]
	public function testCanAccessFilesystemWithScope(array $scope, bool $expected): void {
		$this->assertSame($expected, $this->managerWithScope($scope)->canAccessFilesystem());
	}

	public function testHasScopeWithoutToken(): void {
		$manager = new LockdownManager($this->sessionCallback, new TokenScopes());
		$this->assertTrue($manager->hasScope(TokenScopes::CALENDAR_READ));
	}

	public static function hasScope(): array {
		return [
			'legacy' => [[IToken::SCOPE_FILESYSTEM => true], TokenScopes::CALENDAR_READ, true],
			'granted' => [[TokenScopes::KEY => [TokenScopes::CALENDAR_READ]], TokenScopes::CALENDAR_READ, true],
			'not granted' => [[TokenScopes::KEY => [TokenScopes::CALENDAR_READ]], TokenScopes::FILES_READ, false],
			'malformed' => [[TokenScopes::KEY => TokenScopes::CALENDAR_READ], TokenScopes::CALENDAR_READ, false],
		];
	}

	#[DataProvider('hasScope')]
	public function testHasScope(array $scope, string $requested, bool $expected): void {
		$this->assertSame($expected, $this->managerWithScope($scope)->hasScope($requested));
	}

	public function testIsScopedWithoutToken(): void {
		$manager = new LockdownManager($this->sessionCallback, new TokenScopes());
		$this->assertFalse($manager->isScoped());
	}

	public static function isScoped(): array {
		return [
			'legacy' => [[IToken::SCOPE_FILESYSTEM => true], false],
			'scoped' => [[TokenScopes::KEY => [TokenScopes::CALENDAR_READ]], true],
			'empty scopes' => [[TokenScopes::KEY => []], true],
			'malformed' => [[TokenScopes::KEY => TokenScopes::CALENDAR_READ], true],
		];
	}

	#[DataProvider('isScoped')]
	public function testIsScoped(array $scope, bool $expected): void {
		$this->assertSame($expected, $this->managerWithScope($scope)->isScoped());
	}

	private function managerWithScope(array $scope): LockdownManager {
		$token = new PublicKeyToken();
		$token->setScope($scope);
		$manager = new LockdownManager($this->sessionCallback, new TokenScopes());
		$manager->setToken($token);
		return $manager;
	}
}

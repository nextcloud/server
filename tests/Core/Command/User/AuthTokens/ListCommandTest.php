<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\User\AuthTokens;

use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\TokenScopes;
use OC\Core\Command\User\AuthTokens\ListCommand;
use OCP\Authentication\Token\IToken;
use OCP\IUserManager;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class ListCommandTest extends TestCase {
	public static function plainScopes(): array {
		return [
			'legacy' => [
				[IToken::SCOPE_FILESYSTEM => true, IToken::SCOPE_SKIP_PASSWORD_VALIDATION => true],
				'filesystem, password-unconfirmable',
			],
			'scoped' => [
				[IToken::SCOPE_FILESYSTEM => true, TokenScopes::KEY => [TokenScopes::FILES_READ, TokenScopes::FILES_WRITE]],
				'filesystem, scopes: files:read files:write',
			],
			'empty scopes' => [
				[IToken::SCOPE_FILESYSTEM => false, TokenScopes::KEY => []],
				'scopes: none',
			],
		];
	}

	#[DataProvider('plainScopes')]
	public function testPlainOutputShowsScopes(array $scope, string $expected): void {
		$command = new ListCommand($this->createMock(IUserManager::class), $this->createMock(IProvider::class), new TokenScopes());

		$token = $command->formatTokenForPlainOutput([
			'scope' => $scope,
			'lastActivity' => 0,
			'type' => IToken::PERMANENT_TOKEN,
		]);

		$this->assertSame($expected, $token['scope']);
	}
}

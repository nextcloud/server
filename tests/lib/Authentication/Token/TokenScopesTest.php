<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use OC\Authentication\Token\TokenScopes;
use OCP\Authentication\Token\IToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class TokenScopesTest extends TestCase {
	private TokenScopes $tokenScopes;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->tokenScopes = new TokenScopes();
	}

	public static function merges(): array {
		$fs = IToken::SCOPE_FILESYSTEM;
		$calendar = [$fs => false, TokenScopes::KEY => [TokenScopes::CALENDAR_READ]];
		$deck = [$fs => false, TokenScopes::KEY => ['app:deck']];
		return [
			'legacy token takes requested filesystem' => [[$fs => true], [$fs => false], [$fs => false]],
			'legacy token keeps filesystem when omitted' => [[$fs => false], [], [$fs => false]],
			'scoped token keeps scopes, ignores requested filesystem' => [$calendar, [$fs => true], $calendar],
			'null returns to full access' => [$calendar, [$fs => false, TokenScopes::KEY => null], [$fs => true]],
			'new scopes normalised, filesystem derived' => [
				[$fs => true],
				[TokenScopes::KEY => ['a' => TokenScopes::FILES_WRITE, 3 => TokenScopes::FILES_READ, 7 => TokenScopes::FILES_WRITE]],
				[$fs => true, TokenScopes::KEY => [TokenScopes::FILES_READ, TokenScopes::FILES_WRITE]],
			],
			'empty scopes deny filesystem' => [[$fs => true], [TokenScopes::KEY => []], [$fs => false, TokenScopes::KEY => []]],
			'unchanged scopes skip validation' => [$deck, $deck, $deck],
			'stored server keys are kept' => [
				[IToken::SCOPE_SKIP_PASSWORD_VALIDATION => true, $fs => true],
				[IToken::SCOPE_SKIP_PASSWORD_VALIDATION => false],
				[IToken::SCOPE_SKIP_PASSWORD_VALIDATION => true, $fs => true],
			],
			'requested server keys are ignored' => [[$fs => true], [IToken::SCOPE_SKIP_PASSWORD_VALIDATION => true], [$fs => true]],
		];
	}

	#[DataProvider('merges')]
	public function testMergeUpdate(array $stored, array $requested, array $expected): void {
		$this->assertSame($expected, $this->tokenScopes->mergeUpdate($stored, $requested));
	}

	public static function invalidUpdates(): array {
		return [
			'unknown scope' => [[TokenScopes::KEY => ['files:delete']]],
			'per-app grant' => [[TokenScopes::KEY => ['app:deck']]],
			'shared service' => [[TokenScopes::KEY => ['search:read']]],
			'write without read' => [[TokenScopes::KEY => [TokenScopes::FILES_WRITE]]],
			'share without read' => [[TokenScopes::KEY => [TokenScopes::FILES_SHARE]]],
			'calendar write without read' => [[TokenScopes::KEY => [TokenScopes::CALENDAR_WRITE]]],
			'read-only files' => [[TokenScopes::KEY => [TokenScopes::FILES_READ]]],
			'read and share without write' => [[TokenScopes::KEY => [TokenScopes::FILES_READ, TokenScopes::FILES_SHARE]]],
			'not an array' => [[TokenScopes::KEY => TokenScopes::FILES_READ]],
			'nested array entry' => [[TokenScopes::KEY => [TokenScopes::CALENDAR_READ, ['x']]]],
			'non-bool filesystem' => [[IToken::SCOPE_FILESYSTEM => 'false']],
		];
	}

	#[DataProvider('invalidUpdates')]
	public function testInvalidUpdateIsRejected(array $requested): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->tokenScopes->mergeUpdate([IToken::SCOPE_FILESYSTEM => true], $requested);
	}

	public static function blobs(): array {
		return [
			'legacy' => [[IToken::SCOPE_FILESYSTEM => true], null],
			'scoped' => [[TokenScopes::KEY => [TokenScopes::FILES_READ]], [TokenScopes::FILES_READ]],
			'empty' => [[TokenScopes::KEY => []], []],
			'string' => [[TokenScopes::KEY => TokenScopes::FILES_READ], []],
			'null' => [[TokenScopes::KEY => null], []],
			'map' => [[TokenScopes::KEY => ['a' => TokenScopes::FILES_READ]], [TokenScopes::FILES_READ]],
			'int entry' => [[TokenScopes::KEY => [TokenScopes::FILES_READ, 1]], []],
		];
	}

	#[DataProvider('blobs')]
	public function testFromBlob(array $blob, ?array $expected): void {
		$this->assertSame($expected, $this->tokenScopes->fromBlob($blob));
	}
}

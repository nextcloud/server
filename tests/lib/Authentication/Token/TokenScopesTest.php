<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use OC\Authentication\Token\TokenScopes;
use OCA\Files\TokenScopes as FilesTokenScopes;
use OCA\Files_Sharing\TokenScopes as SharingTokenScopes;
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
		$calendar = [$fs => false, TokenScopes::KEY => ['dav:calendar:read']];
		$deck = [$fs => false, TokenScopes::KEY => ['app:deck']];
		return [
			'legacy token takes requested filesystem' => [[$fs => true], [$fs => false], [$fs => false]],
			'legacy token keeps filesystem when omitted' => [[$fs => false], [], [$fs => false]],
			'scoped token keeps scopes, ignores requested filesystem' => [$calendar, [$fs => true], $calendar],
			'null returns to full access' => [$calendar, [$fs => false, TokenScopes::KEY => null], [$fs => true]],
			'new scopes normalised, filesystem derived' => [
				[$fs => true],
				[TokenScopes::KEY => ['a' => FilesTokenScopes::FILES_WRITE, 3 => FilesTokenScopes::FILES_READ, 7 => FilesTokenScopes::FILES_WRITE]],
				[$fs => true, TokenScopes::KEY => [FilesTokenScopes::FILES_READ, FilesTokenScopes::FILES_WRITE]],
			],
			'files with sharing' => [
				[$fs => true],
				[TokenScopes::KEY => [FilesTokenScopes::FILES_READ, FilesTokenScopes::FILES_WRITE, SharingTokenScopes::SHARES_WRITE]],
				[$fs => true, TokenScopes::KEY => [FilesTokenScopes::FILES_READ, FilesTokenScopes::FILES_WRITE, SharingTokenScopes::SHARES_WRITE]],
			],
			'empty scopes deny filesystem' => [[$fs => true], [TokenScopes::KEY => []], [$fs => false, TokenScopes::KEY => []]],
			'unchanged scopes skip validation' => [$deck, $deck, $deck],
			'scopes core does not know are stored' => [[$fs => true], [TokenScopes::KEY => ['app:deck']], $deck],
			'write without read is stored' => [[$fs => true], [TokenScopes::KEY => ['dav:calendar:write']], [$fs => false, TokenScopes::KEY => ['dav:calendar:write']]],
			'stored server keys are kept' => [
				[IToken::SCOPE_SKIP_PASSWORD_VALIDATION => true, $fs => true],
				[IToken::SCOPE_SKIP_PASSWORD_VALIDATION => false],
				[IToken::SCOPE_SKIP_PASSWORD_VALIDATION => true, $fs => true],
			],
			'requested server keys are ignored' => [[$fs => true], [IToken::SCOPE_SKIP_PASSWORD_VALIDATION => true], [$fs => true]],
		];
	}

	#[DataProvider('merges')]
	public function testUpdateScope(array $stored, array $requested, array $expected): void {
		$this->assertSame($expected, $this->tokenScopes->updateScope($stored, $requested, []));
	}

	public static function invalidUpdates(): array {
		return [
			'files write without read' => [[TokenScopes::KEY => [FilesTokenScopes::FILES_WRITE]]],
			'files share without read' => [[TokenScopes::KEY => [SharingTokenScopes::SHARES_WRITE]]],
			'files write and share without read' => [[TokenScopes::KEY => [FilesTokenScopes::FILES_WRITE, SharingTokenScopes::SHARES_WRITE]]],
			'read-only files' => [[TokenScopes::KEY => [FilesTokenScopes::FILES_READ]]],
			'trash without files' => [[TokenScopes::KEY => ['files_trashbin:dav:read']]],
			'versions without files' => [[TokenScopes::KEY => ['files_versions:dav:read']]],
			'read and share without write' => [[TokenScopes::KEY => [FilesTokenScopes::FILES_READ, SharingTokenScopes::SHARES_WRITE]]],
			'not an array' => [[TokenScopes::KEY => FilesTokenScopes::FILES_READ]],
			'nested array entry' => [[TokenScopes::KEY => ['dav:calendar:read', ['x']]]],
			'empty scope' => [[TokenScopes::KEY => ['']]],
			'scope too long' => [[TokenScopes::KEY => [str_repeat('a', 65)]]],
			'too many scopes' => [[TokenScopes::KEY => array_map(static fn (int $i) => 'app:' . $i, range(1, 101))]],
			'non-bool filesystem' => [[IToken::SCOPE_FILESYSTEM => 'false']],
		];
	}

	#[DataProvider('invalidUpdates')]
	public function testInvalidUpdateIsRejected(array $requested): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->tokenScopes->updateScope([IToken::SCOPE_FILESYSTEM => true], $requested, []);
	}

	public function testAppGrant(): void {
		$this->assertSame(
			[IToken::SCOPE_FILESYSTEM => false, TokenScopes::KEY => ['deck']],
			$this->tokenScopes->updateScope([IToken::SCOPE_FILESYSTEM => true], [TokenScopes::KEY => ['deck']], ['deck']),
		);
	}

	public function testStoredAppGrantSurvivesTheAppLeaving(): void {
		$stored = [IToken::SCOPE_FILESYSTEM => false, TokenScopes::KEY => ['deck']];
		$this->assertSame(
			[IToken::SCOPE_FILESYSTEM => false, TokenScopes::KEY => ['dav:calendar:read', 'deck']],
			$this->tokenScopes->updateScope($stored, [TokenScopes::KEY => ['deck', 'dav:calendar:read']], []),
		);
	}

	public static function invalidAppGrants(): array {
		return [
			'app not grantable' => ['notes'],
			'capability' => ['files'],
		];
	}

	#[DataProvider('invalidAppGrants')]
	public function testInvalidAppGrantIsRejected(string $scope): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->tokenScopes->updateScope([IToken::SCOPE_FILESYSTEM => true], [TokenScopes::KEY => [$scope]], ['deck']);
	}

	public static function blobs(): array {
		return [
			'legacy' => [[IToken::SCOPE_FILESYSTEM => true], null],
			'scoped' => [[TokenScopes::KEY => [FilesTokenScopes::FILES_READ]], [FilesTokenScopes::FILES_READ]],
			'empty' => [[TokenScopes::KEY => []], []],
			'string' => [[TokenScopes::KEY => FilesTokenScopes::FILES_READ], []],
			'null' => [[TokenScopes::KEY => null], []],
			'map' => [[TokenScopes::KEY => ['a' => FilesTokenScopes::FILES_READ]], [FilesTokenScopes::FILES_READ]],
			'int entry' => [[TokenScopes::KEY => [FilesTokenScopes::FILES_READ, 1]], []],
		];
	}

	#[DataProvider('blobs')]
	public function testFromBlob(array $blob, ?array $expected): void {
		$this->assertSame($expected, $this->tokenScopes->fromBlob($blob));
	}
}

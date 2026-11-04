<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Actor;

use Closure;
use InvalidArgumentException;
use OCP\Actor\Principal;
use OCP\Actor\PrincipalType;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class PrincipalTest extends TestCase {
	public static function dataFactories(): array {
		return [
			'user' => [fn () => Principal::user('alice'), PrincipalType::User, 'alice', null, null, 'user|alice'],
			'federated user' => [fn () => Principal::user('bob', 'cloud.example'), PrincipalType::User, 'bob', 'cloud.example', null, 'user|bob@cloud.example'],
			'anonymous' => [
				fn () => Principal::anonymous('sharetoken', 'files_sharing:visitor'),
				PrincipalType::Anonymous, 'sharetoken', null, 'files_sharing:visitor', 'anonymous|sharetoken|files_sharing:visitor',
			],
			'anonymous with visitor id' => [
				fn () => Principal::anonymous('roomtoken', 'spreed:guest', 'guestid'),
				PrincipalType::Anonymous, 'roomtoken', 'guestid', 'spreed:guest', 'anonymous|roomtoken@guestid|spreed:guest',
			],
			'anonymous with digits, underscore and hyphen in kind' => [
				fn () => Principal::anonymous('formhash', 'forms:public_respondent-2'),
				PrincipalType::Anonymous, 'formhash', null, 'forms:public_respondent-2', 'anonymous|formhash|forms:public_respondent-2',
			],
			'federated user with https remote and trailing slash' => [
				fn () => Principal::user('bob', 'https://cloud.example/nextcloud/'),
				PrincipalType::User, 'bob', 'cloud.example/nextcloud', null, 'user|bob@cloud.example/nextcloud',
			],
			'federated user with http remote' => [fn () => Principal::user('bob', 'http://cloud.example'), PrincipalType::User, 'bob', 'http://cloud.example', null, 'user|bob@http://cloud.example'],
			'bot' => [fn () => Principal::bot('summarizer', 'spreed'), PrincipalType::Bot, 'summarizer', null, 'spreed', 'bot|summarizer|spreed'],
			'system' => [fn () => Principal::system('retention', 'files_versions'), PrincipalType::System, 'retention', null, 'files_versions', 'system|retention|files_versions'],
			'core system component' => [fn () => Principal::system('occ', 'core'), PrincipalType::System, 'occ', null, 'core', 'system|occ|core'],
			'local user with delimiter in id' => [fn () => Principal::user('bob@cloud.example'), PrincipalType::User, 'bob@cloud.example', null, null, 'user|bob%40cloud.example'],
			'anonymous with delimiters' => [
				fn () => Principal::anonymous('tok@en', 'files_sharing:visitor', 'a|b#c%'),
				PrincipalType::Anonymous, 'tok@en', 'a|b#c%', 'files_sharing:visitor', 'anonymous|tok%40en@a%7Cb%23c%25|files_sharing:visitor',
			],
		];
	}

	#[DataProvider(methodName: 'dataFactories')]
	public function testFactories(Closure $create, PrincipalType $type, string $id, ?string $origin, ?string $source, string $key): void {
		$principal = $create();
		$this->assertSame($type, $principal->type);
		$this->assertSame($id, $principal->id);
		$this->assertSame($origin, $principal->origin);
		$this->assertSame($source, $principal->source);
		$this->assertSame($key, $principal->key());
	}

	public static function dataInvalid(): array {
		return [
			'federated user with empty remote' => [fn () => Principal::user('bob', ''), 'Invalid origin for principal of type "user"'],
			'anonymous with empty visitor id' => [fn () => Principal::anonymous('sharetoken', 'files_sharing:visitor', ''), 'Invalid origin for principal of type "anonymous"'],
			'anonymous with empty source' => [fn () => Principal::anonymous('sharetoken', ''), 'Invalid source for principal of type "anonymous"'],
			'anonymous with source without kind' => [fn () => Principal::anonymous('sharetoken', 'files_sharing'), 'Invalid source "files_sharing"'],
			'anonymous with empty kind' => [fn () => Principal::anonymous('sharetoken', 'files_sharing:'), 'Invalid source "files_sharing:"'],
			'anonymous with double colon' => [fn () => Principal::anonymous('sharetoken', 'files_sharing::'), 'Invalid source "files_sharing::"'],
			'anonymous with nested kind' => [fn () => Principal::anonymous('sharetoken', 'files_sharing:visitor:x'), 'Invalid source "files_sharing:visitor:x"'],
			'anonymous with trailing newline in kind' => [fn () => Principal::anonymous('sharetoken', "files_sharing:visitor\n"), "Invalid source \"files_sharing:visitor\n\""],
			'anonymous with uppercase kind' => [fn () => Principal::anonymous('sharetoken', 'files_sharing:Visitor'), 'Invalid source "files_sharing:Visitor"'],
			'anonymous with delimiter in kind' => [fn () => Principal::anonymous('sharetoken', 'files_sharing:visit|or'), 'Invalid source "files_sharing:visit|or"'],
			'anonymous with trailing newline in app id' => [fn () => Principal::anonymous('sharetoken', "files_sharing\n:visitor"), "Invalid app id \"files_sharing\n\""],
			'anonymous with invalid app id' => [fn () => Principal::anonymous('sharetoken', 'Files:visitor'), 'Invalid app id "Files"'],
			'bot with invalid app id' => [fn () => Principal::bot('summarizer', 'Spreed'), 'Invalid app id "Spreed"'],
			'system with invalid app id' => [fn () => Principal::system('retention', 'files versions'), 'Invalid app id "files versions"'],
			'bot with empty app id' => [fn () => Principal::bot('summarizer', ''), 'Invalid source for principal of type "bot"'],
			'system with empty app id' => [fn () => Principal::system('retention', ''), 'Invalid source for principal of type "system"'],
			'federated user with remote that is only a scheme' => [fn () => Principal::user('bob', 'https://'), 'Invalid origin for principal of type "user"'],
			'bot without app id from array' => [fn () => Principal::fromArray(['type' => 'bot', 'id' => 'summarizer']), 'Invalid source for principal of type "bot"'],
			'system without app id from array' => [fn () => Principal::fromArray(['type' => 'system', 'id' => 'retention']), 'Invalid source for principal of type "system"'],
			'empty type' => [fn () => Principal::fromArray(['type' => '', 'id' => 'alice']), 'Invalid principal data'],
			'empty id' => [fn () => Principal::fromArray(['type' => 'user', 'id' => '']), 'Invalid principal data'],
			'empty origin' => [fn () => Principal::fromArray(['type' => 'user', 'id' => 'bob', 'origin' => '']), 'Invalid principal data'],
			'empty source' => [fn () => Principal::fromArray(['type' => 'anonymous', 'id' => 'sharetoken', 'source' => '']), 'Invalid principal data'],
			'missing type' => [fn () => Principal::fromArray(['id' => 'alice']), 'Invalid principal data'],
			'missing id' => [fn () => Principal::fromArray(['type' => 'user']), 'Invalid principal data'],
			'non-string type' => [fn () => Principal::fromArray(['type' => 1, 'id' => 'alice']), 'Invalid principal data'],
			'non-string id' => [fn () => Principal::fromArray(['type' => 'user', 'id' => 1]), 'Invalid principal data'],
			'non-string origin' => [fn () => Principal::fromArray(['type' => 'user', 'id' => 'bob', 'origin' => 1]), 'Invalid principal data'],
			'non-string source' => [fn () => Principal::fromArray(['type' => 'bot', 'id' => 'summarizer', 'source' => 1]), 'Invalid principal data'],
			'unknown type' => [fn () => Principal::fromArray(['type' => 'nope', 'id' => 'alice']), 'Unknown principal type "nope"'],
			'user with source' => [fn () => Principal::fromArray(['type' => 'user', 'id' => 'alice', 'source' => 'spreed']), 'Invalid source for principal of type "user"'],
			'federated user with source' => [
				fn () => Principal::fromArray(['type' => 'user', 'id' => 'bob', 'origin' => 'cloud.example', 'source' => 'spreed']),
				'Invalid source for principal of type "user"',
			],
			'anonymous without source from array' => [fn () => Principal::fromArray(['type' => 'anonymous', 'id' => 'sharetoken']), 'Invalid source for principal of type "anonymous"'],
			'anonymous with invalid source from array' => [
				fn () => Principal::fromArray(['type' => 'anonymous', 'id' => 'sharetoken', 'source' => 'visitor']),
				'Invalid source "visitor"',
			],
			'bot with invalid app id from array' => [fn () => Principal::fromArray(['type' => 'bot', 'id' => 'summarizer', 'source' => '../spreed']), 'Invalid app id "../spreed"'],
			'system with invalid app id from array' => [fn () => Principal::fromArray(['type' => 'system', 'id' => 'sync', 'source' => 'Calendar']), 'Invalid app id "Calendar"'],
			'bot with origin' => [fn () => Principal::fromArray(['type' => 'bot', 'id' => 'summarizer', 'origin' => 'cloud.example', 'source' => 'spreed']), 'Invalid origin for principal of type "bot"'],
			'system with origin' => [fn () => Principal::fromArray(['type' => 'system', 'id' => 'sync', 'origin' => 'cloud.example', 'source' => 'calendar']), 'Invalid origin for principal of type "system"'],
		];
	}

	#[DataProvider(methodName: 'dataInvalid')]
	public function testInvalid(Closure $create, string $message): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($message);
		$create();
	}

	public static function dataEquals(): array {
		$anonymous = fn () => Principal::anonymous('sharetoken', 'files_sharing:visitor', 'visitor');
		return [
			'same' => [$anonymous, fn () => Principal::anonymous('sharetoken', 'files_sharing:visitor', 'visitor'), true],
			'different source' => [$anonymous, fn () => Principal::anonymous('sharetoken', 'spreed:guest', 'visitor'), false],
			'different scope' => [$anonymous, fn () => Principal::anonymous('other', 'files_sharing:visitor', 'visitor'), false],
			'different visitor id' => [$anonymous, fn () => Principal::anonymous('sharetoken', 'files_sharing:visitor', 'other'), false],
			'with and without visitor id' => [$anonymous, fn () => Principal::anonymous('sharetoken', 'files_sharing:visitor'), false],
			'different type' => [fn () => Principal::user('alice'), fn () => Principal::anonymous('alice', 'files_sharing:visitor'), false],
			'federated user with differently written remote' => [fn () => Principal::user('bob', 'cloud.example'), fn () => Principal::user('bob', 'https://cloud.example/'), true],
			'federated user with http and https remote' => [fn () => Principal::user('bob', 'cloud.example'), fn () => Principal::user('bob', 'http://cloud.example'), false],
			'local and federated user' => [fn () => Principal::user('bob'), fn () => Principal::user('bob', 'cloud.example'), false],
			'local user with remote-like id and federated user' => [fn () => Principal::user('bob@cloud.example'), fn () => Principal::user('bob', 'cloud.example'), false],
			'anonymous with delimiter split differently between scope and visitor id' => [
				fn () => Principal::anonymous('a@b', 'files_sharing:visitor', 'c'),
				fn () => Principal::anonymous('a', 'files_sharing:visitor', 'b@c'),
				false,
			],
			'federated users of different remotes' => [fn () => Principal::user('bob', 'cloud.example'), fn () => Principal::user('bob', 'other.example'), false],
			'bots of different apps' => [fn () => Principal::bot('summarizer', 'spreed'), fn () => Principal::bot('summarizer', 'assistant'), false],
			'same component name of different apps' => [fn () => Principal::system('retention', 'files'), fn () => Principal::system('retention', 'files_versions'), false],
		];
	}

	#[DataProvider(methodName: 'dataEquals')]
	public function testEquals(Closure $createA, Closure $createB, bool $expected): void {
		$a = $createA();
		$b = $createB();
		$this->assertSame($expected, $a->equals($b));
		$this->assertSame($expected, $b->equals($a));
		$this->assertSame($expected, $a->key() === $b->key());
	}

	public function testJsonSerialize(): void {
		$this->assertSame(
			['type' => 'user', 'id' => 'bob', 'origin' => 'cloud.example', 'source' => null],
			Principal::user('bob', 'cloud.example')->jsonSerialize(),
		);
	}

	#[DataProvider(methodName: 'dataFactories')]
	public function testRoundTrip(Closure $create): void {
		$principal = $create();
		$restored = Principal::fromArray(json_decode(json_encode($principal), true));

		$this->assertTrue($principal->equals($restored));
		$this->assertTrue($principal->equals(Principal::fromArray($principal->jsonSerialize())));
	}

	public function testFromArrayWithoutOptionalKeys(): void {
		$this->assertTrue(Principal::user('alice')->equals(Principal::fromArray(['type' => 'user', 'id' => 'alice'])));
	}
}

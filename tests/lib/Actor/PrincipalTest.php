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
			'federated user' => [fn () => Principal::federatedUser('bob', 'cloud.example'), PrincipalType::User, 'bob', 'cloud.example', null, 'user|bob@cloud.example'],
			'anonymous' => [
				fn () => Principal::anonymous('nickname', 'sharetoken', 'files_sharing:visitor'),
				PrincipalType::Anonymous, 'nickname', 'sharetoken', 'files_sharing:visitor', 'anonymous|nickname@sharetoken|files_sharing:visitor',
			],
			'bot' => [fn () => Principal::bot('summarizer'), PrincipalType::Bot, 'summarizer', null, null, 'bot|summarizer'],
			'bot with source' => [fn () => Principal::bot('summarizer', 'spreed'), PrincipalType::Bot, 'summarizer', null, 'spreed', 'bot|summarizer|spreed'],
			'system' => [fn () => Principal::system('calendar'), PrincipalType::System, 'calendar', null, null, 'system|calendar'],
			'system with source' => [fn () => Principal::system('files_versions', 'retention'), PrincipalType::System, 'files_versions', null, 'retention', 'system|files_versions|retention'],
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
		$this->assertTrue($principal->is($type));
	}

	public static function dataInvalid(): array {
		return [
			'federated user with empty remote' => [fn () => Principal::federatedUser('bob', ''), 'Invalid origin for principal of type "user"'],
			'anonymous with empty token' => [fn () => Principal::anonymous('nickname', '', 'files_sharing:visitor'), 'Invalid origin for principal of type "anonymous"'],
			'anonymous without source' => [fn () => Principal::anonymous('nickname', 'sharetoken'), 'Invalid source for principal of type "anonymous"'],
			'anonymous with empty source' => [fn () => Principal::anonymous('nickname', 'sharetoken', ''), 'Invalid source for principal of type "anonymous"'],
			'bot with empty source' => [fn () => Principal::bot('summarizer', ''), 'Invalid source for principal of type "bot"'],
			'system with empty source' => [fn () => Principal::system('files_versions', ''), 'Invalid source for principal of type "system"'],
			'empty type' => [fn () => Principal::fromArray(['type' => '', 'id' => 'alice']), 'Invalid principal data'],
			'empty id' => [fn () => Principal::fromArray(['type' => 'user', 'id' => '']), 'Invalid principal data'],
			'empty origin' => [fn () => Principal::fromArray(['type' => 'user', 'id' => 'bob', 'origin' => '']), 'Invalid principal data'],
			'empty source' => [fn () => Principal::fromArray(['type' => 'anonymous', 'id' => 'nickname', 'source' => '']), 'Invalid principal data'],
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
			'anonymous without source from array' => [fn () => Principal::fromArray(['type' => 'anonymous', 'id' => 'nickname', 'origin' => 'sharetoken']), 'Invalid source for principal of type "anonymous"'],
			'bot with origin' => [fn () => Principal::fromArray(['type' => 'bot', 'id' => 'summarizer', 'origin' => 'cloud.example']), 'Invalid origin for principal of type "bot"'],
			'system with origin' => [fn () => Principal::fromArray(['type' => 'system', 'id' => 'calendar', 'origin' => 'cloud.example']), 'Invalid origin for principal of type "system"'],
		];
	}

	#[DataProvider(methodName: 'dataInvalid')]
	public function testInvalid(Closure $create, string $message): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($message);
		$create();
	}

	public static function dataEquals(): array {
		$anonymous = fn () => Principal::anonymous('nickname', 'sharetoken', 'files_sharing:visitor');
		return [
			'same' => [$anonymous, fn () => Principal::anonymous('nickname', 'sharetoken', 'files_sharing:visitor'), true],
			'different source' => [$anonymous, fn () => Principal::anonymous('nickname', 'sharetoken', 'spreed:guest'), false],
			'different id' => [$anonymous, fn () => Principal::anonymous('other', 'sharetoken', 'files_sharing:visitor'), false],
			'different origin' => [$anonymous, fn () => Principal::anonymous('nickname', 'other', 'files_sharing:visitor'), false],
			'different type' => [fn () => Principal::user('alice'), fn () => Principal::bot('alice'), false],
			'local and federated user' => [fn () => Principal::user('bob'), fn () => Principal::federatedUser('bob', 'cloud.example'), false],
			'federated users of different remotes' => [fn () => Principal::federatedUser('bob', 'cloud.example'), fn () => Principal::federatedUser('bob', 'other.example'), false],
			'bots of different apps' => [fn () => Principal::bot('summarizer', 'spreed'), fn () => Principal::bot('summarizer', 'assistant'), false],
			'bot with and without app' => [fn () => Principal::bot('summarizer', 'spreed'), fn () => Principal::bot('summarizer'), false],
			'same component name of different apps' => [fn () => Principal::system('files', 'retention'), fn () => Principal::system('files_versions', 'retention'), false],
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
			Principal::federatedUser('bob', 'cloud.example')->jsonSerialize(),
		);
	}

	#[DataProvider(methodName: 'dataFactories')]
	public function testRoundTrip(Closure $create): void {
		$principal = $create();
		$restored = Principal::fromArray(json_decode(json_encode($principal), true));

		$this->assertTrue($principal->equals($restored));
	}

	public function testFromArrayWithoutOptionalKeys(): void {
		$this->assertTrue(Principal::user('alice')->equals(Principal::fromArray(['type' => 'user', 'id' => 'alice'])));
	}
}

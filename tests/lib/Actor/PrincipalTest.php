<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Actor;

use Closure;
use InvalidArgumentException;
use NCU\Actor\Principal;
use NCU\Actor\PrincipalType;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class PrincipalTest extends TestCase {
	public static function dataFactories(): array {
		return [
			'user' => [fn () => Principal::user('alice'), PrincipalType::User, 'alice', null, null, 'user:alice'],
			'federated' => [fn () => Principal::federated('bob', 'cloud.example'), PrincipalType::FederatedUser, 'bob', 'cloud.example', null, 'federated_user:bob@cloud.example'],
			'external with token' => [fn () => Principal::external('spreed.guest', 'g1', 'room'), PrincipalType::External, 'g1', 'room', 'spreed.guest', 'external:g1@room:spreed.guest'],
			'external' => [fn () => Principal::external('spreed.email', 'external_user'), PrincipalType::External, 'external_user', null, 'spreed.email', 'external:external_user:spreed.email'],
			'bot' => [fn () => Principal::bot('summarizer'), PrincipalType::Bot, 'summarizer', null, null, 'bot:summarizer'],
			'bot with source' => [fn () => Principal::bot('summarizer', 'spreed'), PrincipalType::Bot, 'summarizer', null, 'spreed', 'bot:summarizer:spreed'],
			'system' => [fn () => Principal::system('files.retention'), PrincipalType::System, 'files.retention', null, null, 'system:files.retention'],
			'system with source' => [fn () => Principal::system('retention', 'governance'), PrincipalType::System, 'retention', null, 'governance', 'system:retention:governance'],
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
			'federated with empty remote' => [fn () => Principal::federated('bob', ''), 'Invalid origin for principal of type "federated_user"'],
			'external with empty token' => [fn () => Principal::external('spreed.email', 'external_user', ''), 'Invalid origin for principal of type "external"'],
			'external with empty source' => [fn () => Principal::external('', 'external_user'), 'Invalid source for principal of type "external"'],
			'bot with empty source' => [fn () => Principal::bot('summarizer', ''), 'Invalid source for principal of type "bot"'],
			'system with empty source' => [fn () => Principal::system('retention', ''), 'Invalid source for principal of type "system"'],
			'empty type' => [fn () => Principal::fromArray(['type' => '', 'id' => 'alice']), 'Invalid principal data'],
			'empty id' => [fn () => Principal::fromArray(['type' => 'user', 'id' => '']), 'Invalid principal data'],
			'empty origin' => [fn () => Principal::fromArray(['type' => 'federated_user', 'id' => 'bob', 'origin' => '']), 'Invalid principal data'],
			'empty source' => [fn () => Principal::fromArray(['type' => 'external', 'id' => 'external_user', 'source' => '']), 'Invalid principal data'],
			'user with origin' => [fn () => Principal::fromArray(['type' => 'user', 'id' => 'alice', 'origin' => 'x']), 'Invalid origin for principal of type "user"'],
			'user with source' => [fn () => Principal::fromArray(['type' => 'user', 'id' => 'alice', 'source' => 'spreed']), 'Invalid source for principal of type "user"'],
			'federated with source' => [fn () => Principal::fromArray(['type' => 'federated_user', 'id' => 'bob', 'origin' => 'cloud.example', 'source' => 'spreed']), 'Invalid source for principal of type "federated_user"'],
			'federated without remote' => [fn () => Principal::fromArray(['type' => 'federated_user', 'id' => 'bob']), 'Invalid origin for principal of type "federated_user"'],
			'external without source' => [fn () => Principal::fromArray(['type' => 'external', 'id' => 'external_user']), 'Invalid source for principal of type "external"'],
			'missing type' => [fn () => Principal::fromArray(['id' => 'alice']), 'Invalid principal data'],
			'missing id' => [fn () => Principal::fromArray(['type' => 'user']), 'Invalid principal data'],
			'non-string id' => [fn () => Principal::fromArray(['type' => 'user', 'id' => 1]), 'Invalid principal data'],
			'non-string origin' => [fn () => Principal::fromArray(['type' => 'federated_user', 'id' => 'bob', 'origin' => 1]), 'Invalid principal data'],
			'non-string source' => [fn () => Principal::fromArray(['type' => 'external', 'id' => 'external_user', 'source' => 1]), 'Invalid principal data'],
			'unknown type' => [fn () => Principal::fromArray(['type' => 'nope', 'id' => 'alice']), 'Unknown principal type "nope"'],
		];
	}

	#[DataProvider('dataInvalid')]
	public function testInvalid(Closure $create, string $message): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($message);
		$create();
	}

	public static function dataEquals(): array {
		$external = fn () => Principal::external('spreed.email', 'external_user', 'room');
		return [
			'same' => [$external, fn () => Principal::external('spreed.email', 'external_user', 'room'), true],
			'different source' => [$external, fn () => Principal::external('spreed.guest', 'external_user', 'room'), false],
			'different id' => [$external, fn () => Principal::external('spreed.email', 'other_user', 'room'), false],
			'different origin' => [$external, fn () => Principal::external('spreed.email', 'external_user', 'other'), false],
			'no origin' => [$external, fn () => Principal::external('spreed.email', 'external_user'), false],
			'different type' => [fn () => Principal::user('alice'), fn () => Principal::bot('alice'), false],
			'bots of different apps' => [fn () => Principal::bot('summarizer', 'spreed'), fn () => Principal::bot('summarizer', 'assistant'), false],
			'bot with and without app' => [fn () => Principal::bot('summarizer', 'spreed'), fn () => Principal::bot('summarizer'), false],
		];
	}

	#[DataProvider('dataEquals')]
	public function testEquals(Closure $createA, Closure $createB, bool $expected): void {
		$a = $createA();
		$b = $createB();
		$this->assertSame($expected, $a->equals($b));
		$this->assertSame($expected, $b->equals($a));
		$this->assertSame($expected, $a->key() === $b->key());
	}

	#[DataProvider('dataFactories')]
	public function testRoundTrip(Closure $create): void {
		$principal = $create();
		$restored = Principal::fromArray(json_decode(json_encode($principal), true));

		$this->assertTrue($principal->equals($restored));
	}

	public function testFromArrayWithoutOptionalKeys(): void {
		$this->assertTrue(Principal::user('alice')->equals(Principal::fromArray(['type' => 'user', 'id' => 'alice'])));
	}
}

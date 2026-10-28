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
			'federated' => [fn () => Principal::federated('bob', 'cloud.example'), PrincipalType::Federated, 'bob', 'cloud.example', null, 'federated:bob@cloud.example'],
			'guest with token' => [fn () => Principal::guest('spreed.guest', 'g1', 'room'), PrincipalType::Guest, 'g1', 'room', 'spreed.guest', 'guest:spreed.guest:g1@room'],
			'guest without token' => [fn () => Principal::guest('files_sharing.link', 'g1'), PrincipalType::Guest, 'g1', null, 'files_sharing.link', 'guest:files_sharing.link:g1'],
			'bot' => [fn () => Principal::bot('summarizer'), PrincipalType::Bot, 'summarizer', null, null, 'bot:summarizer'],
			'system' => [fn () => Principal::system('files.retention'), PrincipalType::System, 'files.retention', null, null, 'system:files.retention'],
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
			'empty id' => [fn () => Principal::user(''), 'Principal id must not be empty'],
			'federated with empty remote' => [fn () => Principal::federated('bob', ''), 'Invalid origin for principal of type "federated"'],
			'guest with empty token' => [fn () => Principal::guest('spreed.guest', 'g1', ''), 'Invalid origin for principal of type "guest"'],
			'guest with empty source' => [fn () => Principal::guest('', 'g1'), 'Invalid source for principal of type "guest"'],
			'user with origin' => [fn () => Principal::fromArray(['type' => 'user', 'id' => 'alice', 'origin' => 'x']), 'Invalid origin for principal of type "user"'],
			'bot with source' => [fn () => Principal::fromArray(['type' => 'bot', 'id' => 'b', 'source' => 'spreed.guest']), 'Invalid source for principal of type "bot"'],
			'federated without remote' => [fn () => Principal::fromArray(['type' => 'federated', 'id' => 'bob']), 'Invalid origin for principal of type "federated"'],
			'guest without source' => [fn () => Principal::fromArray(['type' => 'guest', 'id' => 'g1']), 'Invalid source for principal of type "guest"'],
			'missing type' => [fn () => Principal::fromArray(['id' => 'alice']), 'Invalid principal data'],
			'missing id' => [fn () => Principal::fromArray(['type' => 'user']), 'Invalid principal data'],
			'non-string id' => [fn () => Principal::fromArray(['type' => 'user', 'id' => 1]), 'Invalid principal data'],
			'non-string origin' => [fn () => Principal::fromArray(['type' => 'federated', 'id' => 'bob', 'origin' => 1]), 'Invalid principal data'],
			'non-string source' => [fn () => Principal::fromArray(['type' => 'guest', 'id' => 'g1', 'source' => 1]), 'Invalid principal data'],
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
		$guest = fn () => Principal::guest('spreed.guest', 'g1', 'room');
		return [
			'same' => [$guest, fn () => Principal::guest('spreed.guest', 'g1', 'room'), true],
			'different source' => [$guest, fn () => Principal::guest('spreed.email', 'g1', 'room'), false],
			'different id' => [$guest, fn () => Principal::guest('spreed.guest', 'g2', 'room'), false],
			'different origin' => [$guest, fn () => Principal::guest('spreed.guest', 'g1', 'other'), false],
			'no origin' => [$guest, fn () => Principal::guest('spreed.guest', 'g1'), false],
			'different type' => [fn () => Principal::user('alice'), fn () => Principal::bot('alice'), false],
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

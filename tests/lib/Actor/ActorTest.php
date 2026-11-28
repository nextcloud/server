<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Actor;

use Closure;
use InvalidArgumentException;
use LogicException;
use OCP\Actor\Actor;
use OCP\Actor\Principal;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class ActorTest extends TestCase {

	public static function dataActors(): array {
		return [
			'user' => [fn () => Actor::of(Principal::user('alice')), 'user|alice', null, 'user|alice'],
			'federated user' => [fn () => Actor::of(Principal::user('bob', 'cloud.example')), 'user|bob@cloud.example', null, 'user|bob@cloud.example'],
			'anonymous' => [
				fn () => Actor::of(Principal::anonymous('sharetoken', 'files_sharing:visitor')),
				'anonymous|sharetoken|files_sharing:visitor', null, 'anonymous|sharetoken|files_sharing:visitor',
			],
			'system' => [fn () => Actor::of(Principal::system('sync', 'calendar')), 'system|sync|calendar', null, 'system|sync|calendar'],
			'user acts for user' => [
				fn () => Actor::of(Principal::user('alice'))->onBehalfOf(Principal::user('bob')),
				'user|bob', 'user|alice', 'user|bob#user|alice',
			],
			'bot acts for user' => [
				fn () => Actor::of(Principal::bot('summarizer', 'spreed'))->onBehalfOf(Principal::user('alice')),
				'user|alice', 'bot|summarizer|spreed', 'user|alice#bot|summarizer|spreed',
			],
			'system acts for user' => [
				fn () => Actor::of(Principal::system('retention', 'files_versions'))->onBehalfOf(Principal::user('alice')),
				'user|alice', 'system|retention|files_versions', 'user|alice#system|retention|files_versions',
			],
			'user acts for system' => [
				fn () => Actor::of(Principal::user('alice'))->onBehalfOf(Principal::system('occ', 'core')),
				'system|occ|core', 'user|alice', 'system|occ|core#user|alice',
			],
			'anonymous with delegate delimiter in visitor id' => [
				fn () => Actor::of(Principal::anonymous('sharetoken', 'files_sharing:visitor', 'v#user|alice')),
				'anonymous|sharetoken@v%23user%7Calice|files_sharing:visitor', null, 'anonymous|sharetoken@v%23user%7Calice|files_sharing:visitor',
			],
		];
	}

	/**
	 * @param Closure(): Actor $createActor
	 */
	#[DataProvider(methodName: 'dataActors')]
	public function testActors(Closure $createActor, string $principalKey, ?string $delegateKey, string $key): void {
		$actor = $createActor();

		$this->assertSame($principalKey, $actor->principal->key());
		$this->assertSame($delegateKey, $actor->delegate?->key());
		$this->assertSame($delegateKey !== null, $actor->isDelegated());
		$this->assertSame($key, $actor->key());
	}

	public function testOnBehalfOf(): void {
		$alice = Principal::user('alice');
		$bob = Principal::user('bob');
		$actor = Actor::of($alice);
		$delegated = $actor->onBehalfOf($bob);

		$this->assertSame($bob, $delegated->principal);
		$this->assertSame($alice, $delegated->delegate);
		$this->assertSame($alice, $actor->principal);
		$this->assertNull($actor->delegate);
	}

	public static function dataInvalidOnBehalfOf(): array {
		return [
			'already delegated' => [
				fn () => Actor::of(Principal::user('alice'))->onBehalfOf(Principal::user('bob'))->onBehalfOf(Principal::user('carol')),
				LogicException::class,
				'A delegated actor cannot act on behalf of another principal',
			],
			'self' => [
				fn () => Actor::of(Principal::user('alice'))->onBehalfOf(Principal::user('alice')),
				InvalidArgumentException::class,
				'A principal cannot impersonate itself',
			],
		];
	}

	/**
	 * @param Closure(): Actor $createActor
	 * @param class-string<\Throwable> $exception
	 */
	#[DataProvider(methodName: 'dataInvalidOnBehalfOf')]
	public function testInvalidOnBehalfOf(Closure $createActor, string $exception, string $message): void {
		$this->expectException($exception);
		$this->expectExceptionMessage($message);
		$createActor();
	}

	public function testJsonSerialize(): void {
		$this->assertSame(
			'{"principal":{"type":"user","id":"alice","origin":null,"source":null},"delegate":{"type":"bot","id":"summarizer","origin":null,"source":"assistant"}}',
			json_encode(Actor::of(Principal::bot('summarizer', 'assistant'))->onBehalfOf(Principal::user('alice'))),
		);
		$this->assertSame(
			'{"principal":{"type":"user","id":"alice","origin":null,"source":null},"delegate":null}',
			json_encode(Actor::of(Principal::user('alice'))),
		);
	}

	/**
	 * @param Closure(): Actor $createActor
	 */
	#[DataProvider(methodName: 'dataActors')]
	public function testRoundTrip(Closure $createActor): void {
		$actor = $createActor();
		$json = json_encode($actor);
		$restored = Actor::fromArray(json_decode($json, true));

		$this->assertSame($actor->key(), $restored->key());
		$this->assertSame($json, json_encode($restored));
		$this->assertTrue($actor->equals($restored));
	}

	/**
	 * @param Closure(): Actor $createActor
	 */
	#[DataProvider(methodName: 'dataActors')]
	public function testRoundTripWithoutJson(Closure $createActor): void {
		$actor = $createActor();

		$this->assertTrue($actor->equals(Actor::fromArray($actor->jsonSerialize())));
	}

	public static function dataEquals(): array {
		$alice = fn () => Actor::of(Principal::user('alice'));
		$botForAlice = fn () => Actor::of(Principal::bot('summarizer', 'assistant'))->onBehalfOf(Principal::user('alice'));
		return [
			'same principal' => [$alice, $alice, true],
			'different principal' => [$alice, fn () => Actor::of(Principal::user('bob')), false],
			'same delegation' => [$botForAlice, $botForAlice, true],
			'different delegate' => [$botForAlice, fn () => Actor::of(Principal::user('bob'))->onBehalfOf(Principal::user('alice')), false],
			'delegated and not delegated' => [$botForAlice, $alice, false],
		];
	}

	/**
	 * @param Closure(): Actor $createA
	 * @param Closure(): Actor $createB
	 */
	#[DataProvider(methodName: 'dataEquals')]
	public function testEquals(Closure $createA, Closure $createB, bool $expected): void {
		$a = $createA();
		$b = $createB();
		$this->assertSame($expected, $a->equals($b));
		$this->assertSame($expected, $b->equals($a));
	}

	public function testFromArrayWithoutDelegate(): void {
		$actor = Actor::fromArray(['principal' => ['type' => 'user', 'id' => 'alice']]);

		$this->assertTrue($actor->principal->equals(Principal::user('alice')));
		$this->assertNull($actor->delegate);
		$this->assertFalse($actor->isDelegated());
	}

	public static function dataInvalidFromArray(): array {
		$alice = ['type' => 'user', 'id' => 'alice'];
		return [
			'missing principal' => [[], 'Invalid actor data'],
			'null principal' => [['principal' => null, 'delegate' => $alice], 'Invalid actor data'],
			'non-array principal' => [['principal' => 'alice'], 'Invalid actor data'],
			'non-array delegate' => [['principal' => $alice, 'delegate' => 'admin'], 'Invalid actor data'],
			'invalid principal' => [['principal' => ['type' => 'user']], 'Invalid principal data'],
			'invalid delegate' => [['principal' => $alice, 'delegate' => ['type' => 'user']], 'Invalid principal data'],
			'unknown principal type' => [['principal' => ['type' => 'nope', 'id' => 'alice']], 'Unknown principal type "nope"'],
			'invalid delegate principal' => [
				['principal' => $alice, 'delegate' => ['type' => 'user', 'id' => 'admin', 'source' => 'spreed']],
				'Invalid source for principal of type "user"',
			],
			'delegate equals principal' => [['principal' => $alice, 'delegate' => $alice], 'A principal cannot impersonate itself'],
		];
	}

	#[DataProvider(methodName: 'dataInvalidFromArray')]
	public function testInvalidFromArray(array $data, string $message): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($message);
		Actor::fromArray($data);
	}
}

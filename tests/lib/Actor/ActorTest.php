<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Actor;

use Closure;
use InvalidArgumentException;
use OCP\Actor\Actor;
use OCP\Actor\Principal;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class ActorTest extends TestCase {

	public static function dataActors(): array {
		return [
			'user' => [fn () => Actor::of(Principal::user('alice')), 'user|alice', null, 'user|alice'],
			'federated user' => [fn () => Actor::of(Principal::federatedUser('bob', 'cloud.example')), 'user|bob@cloud.example', null, 'user|bob@cloud.example'],
			'anonymous' => [
				fn () => Actor::of(Principal::anonymous('nickname', 'sharetoken', 'files_sharing:visitor')),
				'anonymous|nickname@sharetoken|files_sharing:visitor', null, 'anonymous|nickname@sharetoken|files_sharing:visitor',
			],
			'system' => [fn () => Actor::of(Principal::system('calendar', 'sync')), 'system|calendar|sync', null, 'system|calendar|sync'],
			'user impersonates user' => [
				fn () => Actor::of(Principal::user('alice'))->impersonate(Principal::user('bob')),
				'user|bob', 'user|alice', 'user|bob#user|alice',
			],
			'bot acts for user' => [
				fn () => Actor::of(Principal::bot('summarizer', 'spreed'))->impersonate(Principal::user('alice')),
				'user|alice', 'bot|summarizer|spreed', 'user|alice#bot|summarizer|spreed',
			],
			'system acts for user' => [
				fn () => Actor::of(Principal::system('files_versions', 'retention'))->impersonate(Principal::user('alice')),
				'user|alice', 'system|files_versions|retention', 'user|alice#system|files_versions|retention',
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

	public function testImpersonate(): void {
		$alice = Principal::user('alice');
		$bob = Principal::user('bob');
		$actor = Actor::of($alice);
		$impersonated = $actor->impersonate($bob);

		$this->assertSame($bob, $impersonated->principal);
		$this->assertSame($alice, $impersonated->delegate);
		$this->assertSame($alice, $actor->principal);
		$this->assertNull($actor->delegate);
	}

	public function testJsonSerialize(): void {
		$this->assertSame(
			'{"principal":{"type":"user","id":"alice","origin":null,"source":null},"delegate":{"type":"bot","id":"summarizer","origin":null,"source":null}}',
			json_encode(Actor::of(Principal::bot('summarizer'))->impersonate(Principal::user('alice'))),
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
		];
	}

	#[DataProvider(methodName: 'dataInvalidFromArray')]
	public function testInvalidFromArray(array $data, string $message): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($message);
		Actor::fromArray($data);
	}
}

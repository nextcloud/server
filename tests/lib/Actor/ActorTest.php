<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Actor;

use Closure;
use InvalidArgumentException;
use NCU\Actor\Actor;
use NCU\Actor\DelegationKind;
use NCU\Actor\Principal;
use NCU\Actor\PrincipalType;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class ActorTest extends TestCase {

	public function testActorOf(): void {
		$alice = Principal::user('alice');
		$actor = Actor::of($alice);

		$this->assertSame($alice, $actor->principal);
		$this->assertNull($actor->delegate);
		$this->assertNull($actor->via);
		$this->assertFalse($actor->isDelegated());
		$this->assertSame([$alice], $actor->chain());
		$this->assertSame($alice, $actor->initiator());
	}

	public static function dataValidLinks(): array {
		return [
			'user impersonates user' => [fn () => Actor::of(Principal::user('admin'))->impersonate(Principal::user('alice')), DelegationKind::Impersonation, ['user:alice', 'user:admin']],
			'bot acts for user' => [fn () => Actor::of(Principal::bot('summarizer'))->actFor(Principal::user('alice')), DelegationKind::OnBehalfOf, ['user:alice', 'bot:summarizer']],
			'bot acts for federated' => [fn () => Actor::of(Principal::bot('summarizer'))->actFor(Principal::federated('bob', 'cloud.example')), DelegationKind::OnBehalfOf, ['federated:bob@cloud.example', 'bot:summarizer']],
			'bot acts for guest' => [fn () => Actor::of(Principal::bot('summarizer'))->actFor(Principal::guest('spreed.guest', 'g1', 'room')), DelegationKind::OnBehalfOf, ['guest:spreed.guest:g1@room', 'bot:summarizer']],
			'system acts for user' => [fn () => Actor::of(Principal::system('files_versions.expire'))->actFor(Principal::user('alice')), DelegationKind::OnBehalfOf, ['user:alice', 'system:files_versions.expire']],
			'elevation acts for user' => [
				fn () => Actor::of(Principal::user('admin'))->elevate(Principal::system('files.retention'))->actFor(Principal::user('alice')),
				DelegationKind::OnBehalfOf, ['user:alice', 'system:files.retention', 'user:admin'],
			],
			'alternating elevation and delegation' => [
				fn () => Actor::of(Principal::user('admin'))->elevate(Principal::system('files.retention'))->actFor(Principal::user('alice'))->elevate(Principal::system('files_versions.expire')),
				DelegationKind::SystemElevation, ['system:files_versions.expire', 'user:alice', 'system:files.retention', 'user:admin'],
			],
			'user elevates' => [fn () => Actor::of(Principal::user('admin'))->elevate(Principal::system('files.retention')), DelegationKind::SystemElevation, ['system:files.retention', 'user:admin']], 'impersonation elevates' => [
				fn () => Actor::of(Principal::user('admin'))->impersonate(Principal::user('alice'))->elevate(Principal::system('files.retention')),
				DelegationKind::SystemElevation, ['system:files.retention', 'user:alice', 'user:admin'],
			],
		];
	}

	/**
	 * @param Closure(): Actor $createActor
	 * @param list<string> $expectedChainKeys
	 */
	#[DataProvider(methodName: 'dataValidLinks')]
	public function testValidLinks(Closure $createActor, DelegationKind $expectedVia, array $expectedChainKeys): void {
		$actor = $createActor();
		$this->assertTrue($actor->isDelegated());
		$this->assertSame($expectedVia, $actor->via);
		$this->assertSame($expectedChainKeys, array_map(static fn (Principal $p) => $p->key(), $actor->chain()));
		$this->assertSame($expectedChainKeys[0], $actor->principal->key());
		$this->assertSame(end($expectedChainKeys), $actor->initiator()->key());
	}

	public static function dataInvalidLinks(): array {
		$admin = static fn () => Actor::of(Principal::user('admin'));
		$bot = static fn () => Actor::of(Principal::bot('summarizer'));
		return [
			'impersonate guest' => [fn () => $admin()->impersonate(Principal::guest('spreed.guest', 'g1')), 'Invalid impersonation: only local users can be impersonated'],
			'impersonate federated' => [fn () => $admin()->impersonate(Principal::federated('bob', 'cloud.example')), 'Invalid impersonation: only local users can be impersonated'],
			'bot impersonates' => [fn () => $bot()->impersonate(Principal::user('alice')), 'Invalid impersonation: only local users can impersonate'],
			'nested impersonation' => [fn () => $admin()->impersonate(Principal::user('alice'))->impersonate(Principal::user('bob')), 'Invalid impersonation: nested impersonation is not allowed'],
			'act for bot' => [fn () => $bot()->actFor(Principal::bot('other')), 'Invalid on_behalf_of: subject must be a human principal'],
			'act for system' => [fn () => $bot()->actFor(Principal::system('files.retention')), 'Invalid on_behalf_of: subject must be a human principal'],
			'user acts for user' => [fn () => $admin()->actFor(Principal::user('alice')), 'Invalid on_behalf_of: only bots and system components act on behalf of others'],
			'elevate to user' => [fn () => $admin()->elevate(Principal::user('alice')), 'Invalid system_elevation: target must be a system principal'],
			'system elevates to system' => [fn () => Actor::of(Principal::system('a'))->elevate(Principal::system('b')), 'Invalid system_elevation: system cannot elevate to system'],
			'act for own initiator' => [
				fn () => Actor::of(Principal::user('alice'))->elevate(Principal::system('files.retention'))->actFor(Principal::user('alice')),
				'Principal appears more than once in delegation chain',
			],
			'self impersonation' => [fn () => $admin()->impersonate(Principal::user('admin')), 'Principal appears more than once in delegation chain'],
		];
	}

	/**
	 * @param Closure(): Actor $createActor
	 */
	#[DataProvider(methodName: 'dataInvalidLinks')]
	public function testInvalidLinks(Closure $createActor, string $expectedExceptionMessage): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($expectedExceptionMessage);
		$createActor();
	}

	public function testInvolves(): void {
		$actor = Actor::of(Principal::bot('summarizer'))->actFor(Principal::user('alice'));

		$this->assertTrue($actor->involves(PrincipalType::Bot));
		$this->assertTrue($actor->involves(PrincipalType::User));
		$this->assertFalse($actor->involves(PrincipalType::System));
	}

	/**
	 * @param Closure(): Actor $createActor
	 */
	#[DataProvider(methodName: 'dataValidLinks')]
	public function testToJsonFromArray(Closure $createActor): void {
		$actor = $createActor();
		$json = json_encode($actor);
		$restored = Actor::fromArray(json_decode($json, true));

		$this->assertSame($json, json_encode($restored));
	}

	public function testMaxChainLength(): void {
		$actor = self::alternatingChain(Actor::MAX_CHAIN_LENGTH);

		$this->assertCount(Actor::MAX_CHAIN_LENGTH, $actor->chain());
	}

	public function testChainTooLong(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Delegation chain too long');
		self::alternatingChain(Actor::MAX_CHAIN_LENGTH + 1);
	}

	/**
	 * Chain of the given length alternating between a system component elevation and acting for a user.
	 */
	private static function alternatingChain(int $length): Actor {
		$actor = Actor::of(Principal::user('user0'));
		for ($i = 1; $i < $length; $i++) {
			$actor = $i % 2 === 1
				? $actor->elevate(Principal::system('component' . $i))
				: $actor->actFor(Principal::user('user' . $i));
		}
		return $actor;
	}

	public static function dataInvalidFromArray(): array {
		$alice = ['type' => 'user', 'id' => 'alice'];
		$admin = ['principal' => ['type' => 'user', 'id' => 'admin']];
		return [
			'missing principal' => [[], 'Invalid actor data'],
			'non-array principal' => [['principal' => 'alice'], 'Invalid actor data'],
			'non-array delegate' => [['principal' => $alice, 'via' => 'impersonation', 'delegate' => 'admin'], 'Invalid actor data'],
			'non-string via' => [['principal' => $alice, 'via' => 1, 'delegate' => $admin], 'Invalid actor data'],
			'unknown via' => [['principal' => $alice, 'via' => 'nope', 'delegate' => $admin], 'Unknown delegation kind "nope"'],
			'invalid principal' => [['principal' => ['type' => 'user']], 'Invalid principal data'],
			'delegate without via' => [['principal' => $alice, 'delegate' => $admin], 'A delegate requires a delegation kind and vice versa'],
			'via without delegate' => [['principal' => $alice, 'via' => 'impersonation'], 'A delegate requires a delegation kind and vice versa'],
			'forbidden link' => [
				['principal' => ['type' => 'guest', 'id' => 'g1', 'source' => 'spreed.guest'], 'via' => 'impersonation', 'delegate' => $admin],
				'Invalid impersonation: only local users can be impersonated',
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

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Actor;

use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use OCP\AppFramework\Attribute\Consumable;
use Override;

/**
 * An "Actor" transports information about whose authority is being claimed to perform an operation.
 *
 * Actors consist of two information:
 * 1. The principal whose authority is being claimed to perform an operation. Permissions are checked against the principal.
 * 2. If someone else performs the operation on the principal's behalf, that party as the delegate,
 *    e.g. a bot or a background job acting for a user. The delegate is kept for auditing and never adds permissions.
 *
 * Creating an Actor asserts nothing.
 * Callers check whether the operation is permitted before creating it.
 * Consumers receiving an Actor from another app, a job payload or a request, check the principal's permissions themselves.
 *
 * ## Examples
 * ```php
 * // 1. User acting as themselves
 * $actor = Actor::of(Principal::user('alice'));
 *
 * // 2. User impersonating another user
 * $actor = Actor::of(Principal::user('alice'))
 *     ->onBehalfOf(Principal::user('bob'));
 *
 * // 3. Bot acting on behalf of a user
 * $actor = Actor::of(Principal::bot('summarizer', 'assistant'))
 *     ->onBehalfOf(Principal::user('alice'));
 *
 * // 4. System component acting as the system itself - e.g. a cron job
 * $actor = Actor::of(Principal::system('sync', 'calendar'));
 *
 * // 5. System component acting on behalf of a user
 * $actor = Actor::of(Principal::system('retention', 'files_versions'))
 *     ->onBehalfOf(Principal::user('alice'));
 *
 * // 6. A federated user acting as itself
 * $actor = Actor::of(Principal::user('bob', 'cloud.example'));
 *
 * // 7. A public visitor acting as itself
 * $actor = Actor::of(Principal::anonymous('public-share-token', 'files_sharing:visitor'));
 * ```
 *
 * @psalm-import-type PrincipalData from Principal
 * @psalm-immutable
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class Actor implements JsonSerializable {
	private function __construct(
		public Principal $principal,
		public ?Principal $delegate = null,
	) {
		if ($delegate !== null && $principal->equals($delegate)) {
			throw new InvalidArgumentException('A principal cannot impersonate itself');
		}
	}

	/**
	 * Actor with no delegation.
	 *
	 * @since 36.0.0
	 */
	public static function of(Principal $principal): self {
		return new self($principal);
	}

	/**
	 * $this acts as $target with the target's full view.
	 *
	 * The returned actor has $target as principal and this actor's principal as delegate.
	 *
	 * @throws InvalidArgumentException - if the target is this actor's own principal
	 * @throws LogicException - if this actor already acts on behalf of another principal
	 * @since 36.0.0
	 */
	public function onBehalfOf(Principal $target): self {
		if ($this->delegate !== null) {
			throw new LogicException('A delegated actor cannot act on behalf of another principal');
		}
		return new self($target, $this->principal);
	}

	/**
	 * @since 36.0.0
	 */
	public function equals(self $other): bool {
		if (!$this->principal->equals($other->principal)) {
			return false;
		}
		if ($this->delegate === null || $other->delegate === null) {
			return $this->delegate === $other->delegate;
		}
		return $this->delegate->equals($other->delegate);
	}

	/**
	 * @since 36.0.0
	 */
	public function isDelegated(): bool {
		return $this->delegate !== null;
	}

	/**
	 * Get a unique key for the Actor.
	 *
	 * @since 36.0.0
	 */
	public function key(): string {
		return $this->principal->key() . ($this->delegate !== null ? '#' . $this->delegate->key() : '');
	}

	/**
	 * Serialize the Actor to an array.
	 *
	 * @return array{principal: PrincipalData, delegate: ?PrincipalData}
	 * @since 36.0.0
	 */
	#[Override]
	public function jsonSerialize(): array {
		return [
			'principal' => $this->principal->jsonSerialize(),
			'delegate' => $this->delegate?->jsonSerialize(),
		];
	}

	/**
	 * Rehydrate from a serialized payload.
	 *
	 * @param array<string, mixed> $data - see `Actor::jsonSerialize`
	 * @throws InvalidArgumentException - if the data is invalid
	 * @since 36.0.0
	 */
	public static function fromArray(array $data): self {
		self::assertValidActorData($data);
		$delegate = $data['delegate'] ?? null;

		return new self(
			Principal::fromArray($data['principal']),
			$delegate !== null ? Principal::fromArray($delegate) : null,
		);
	}

	/**
	 * The principal is validated by Principal::fromArray.
	 *
	 * @psalm-assert array{principal: array<string, mixed>, delegate?: ?array<string, mixed>} $data
	 * @throws InvalidArgumentException -- if the data is invalid
	 */
	private static function assertValidActorData(array $data): void {
		$principal = $data['principal'] ?? null;
		$delegate = $data['delegate'] ?? null;
		if (!is_array($principal) || ($delegate !== null && !is_array($delegate))) {
			throw new InvalidArgumentException('Invalid actor data');
		}
	}
}

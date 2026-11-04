<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor;

use InvalidArgumentException;
use JsonSerializable;
use OCP\AppFramework\Attribute\Consumable;
use Override;

/**
 * An "Actor" transports information about whose authority is being claimed to perform an operation.
 *
 * Actors consist of two information:
 * 1. The principal whose authority is being claimed to perform an operation.
 * 2. If the principal is acting on behalf of someone else, the delegate.
 *
 * Creating an Actor asserts nothing.
 * Callers check whether the operation is permitted before creating it.
 * Consumers receiving an Actor from another app, a job payload or a request, check the principal's permissions themselves.
 *
 * ## Examples
 * ```php
 * // 1. User acting as itself
 * $actor = Actor::of(Principal::user('alice'));
 *
 * // 2. User impersonating another user
 * $actor = Actor::of(Principal::user('alice'))
 *     ->impersonate(Principal::user('bob'));
 *
 * // 3. Bot acting on behalf of a user
 * $actor = Actor::of(Principal::bot('summarizer'))
 *     ->impersonate(Principal::user('alice'));
 *
 * // 4. System component acting as the system itself - e.g. a cron job
 * $actor = Actor::of(Principal::system('calendar', 'sync'));
 *
 * // 5. System component acting on behalf of a user
 * $actor = Actor::of(Principal::system('files_versions', 'retention'))
 *     ->impersonate(Principal::user('alice'));
 *
 * // 6. A federated user acting as itself
 * $actor = Actor::of(Principal::federatedUser('bob', 'cloud.example'));
 *
 * // 7. An public visitor acting as itself
 * $actor = Actor::of(Principal::anonymous('nickname', 'files_sharing:visitor', 'public-share-token'));
 * ```
 *
 * @psalm-immutable
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class Actor implements JsonSerializable {
	private function __construct(
		public Principal $principal,
		public ?Principal $delegate = null,
	) {
	}

	/**
	 * Actor with no delegation.
	 *
	 * @experimental 36.0.0
	 */
	public static function of(Principal $principal): self {
		return new self($principal);
	}

	/**
	 * $this acts as $target with the target's full view.
	 *
	 * @experimental 36.0.0
	 */
	public function impersonate(Principal $target): self {
		return new self($target, $this->principal);
	}

	/**
	 * @experimental 36.0.0
	 */
	public function isDelegated(): bool {
		return $this->delegate !== null;
	}

	/**
	 * Get a unique key for the Actor.
	 *
	 * @experimental 36.0.0
	 */
	public function key(): string {
		return $this->principal->key() . ($this->delegate !== null ? '#' . $this->delegate->key() : '');
	}

	/**
	 * Serialize the Actor to an array.
	 *
	 * @return array{principal: Principal, delegate: ?Principal}
	 * @experimental 36.0.0
	 */
	#[Override]
	public function jsonSerialize(): array {
		return [
			'principal' => $this->principal,
			'delegate' => $this->delegate,
		];
	}

	/**
	 * Rehydrate from a serialized  payload.
	 *
	 * @param array<string, mixed> $data - see `Actor::jsonSerialize`
	 * @throws InvalidArgumentException - if the data is invalid
	 * @experimental 36.0.0
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

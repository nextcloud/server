<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM;

use JsonSerializable;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Immutable representation of an OCM provider's capabilities.
 *
 * Capabilities are advertised by remote OCM peers via the discovery document and
 * indicate which optional features the peer implements (e.g. `exchange-token`,
 * `notifications`, ...). Use the named `has*()` methods for the capabilities
 * defined by the OCM spec, or {@see has()} for arbitrary capability strings.
 *
 * @link https://github.com/cs3org/OCM-API/
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
final class OCMCapabilities implements JsonSerializable {
	/** @var list<string> normalized (lowercased, no leading slash) capability strings */
	private readonly array $capabilities;

	/**
	 * @param list<string> $capabilities
	 * @since 33.0.0
	 */
	public function __construct(array $capabilities = []) {
		$normalized = array_map(
			static fn (string $c): string => strtolower(ltrim($c, '/')),
			$capabilities,
		);
		$this->capabilities = array_values(array_unique($normalized));
	}

	/**
	 * Whether a specific capability is advertised. Convenience for callers that
	 * deal with capabilities not covered by a named `has*()` method.
	 *
	 * @since 33.0.0
	 */
	public function has(string $capability): bool {
		return in_array(strtolower(ltrim($capability, '/')), $this->capabilities, true);
	}

	/**
	 * Whether the peer accepts OCM share notifications.
	 *
	 * @since 33.0.0
	 */
	public function hasNotifications(): bool {
		return $this->has('notifications');
	}

	/**
	 * Whether the peer accepts incoming federated shares.
	 *
	 * @since 33.0.0
	 */
	public function hasShares(): bool {
		return $this->has('shares');
	}

	/**
	 * Whether the peer accepts OCM invitation flow `invite-accepted` callbacks.
	 *
	 * @since 33.0.0
	 */
	public function hasInviteAccepted(): bool {
		return $this->has('invite-accepted');
	}

	/**
	 * Whether the peer implements the OCM token-exchange flow
	 * (refresh token → short-lived access token).
	 *
	 * @since 33.0.0
	 */
	public function hasExchangeToken(): bool {
		return $this->has('exchange-token');
	}

	/**
	 * Raw list of advertised capability strings (normalized).
	 *
	 * @return list<string>
	 * @since 33.0.0
	 */
	public function toArray(): array {
		return $this->capabilities;
	}

	/**
	 * @return list<string>
	 * @since 33.0.0
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return $this->capabilities;
	}
}

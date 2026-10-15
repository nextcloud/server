<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OCM;

use Firebase\JWT\Key;
use OC\Security\Signature\Rfc9421\IJwkResolvingSignatoryManager;
use OCP\Security\Signature\Exceptions\IdentityNotFoundException;
use OCP\Security\Signature\Model\Signatory;

/**
 * Per-call wrapper around {@see OCMSignatoryManager} that swaps in the
 * JWKS-published signatory and sets `rfc9421.format`. Wrapping (vs mutating)
 * keeps the underlying DI-managed instance stateless across requests.
 */
final class Rfc9421SignatoryManager implements IJwkResolvingSignatoryManager {
	public function __construct(
		private readonly OCMSignatoryManager $delegate,
	) {
	}

	#[\Override]
	public function getProviderId(): string {
		return $this->delegate->getProviderId();
	}

	#[\Override]
	public function getOptions(): array {
		return array_merge($this->delegate->getOptions(), ['rfc9421.format' => true]);
	}

	#[\Override]
	public function getLocalSignatory(): Signatory {
		$signatory = $this->delegate->getLocalJwksSignatory();
		if ($signatory === null) {
			throw new IdentityNotFoundException('no JWKS-published signatory available');
		}
		return $signatory;
	}

	#[\Override]
	public function getRemoteSignatory(string $remote): ?Signatory {
		return $this->delegate->getRemoteSignatory($remote);
	}

	#[\Override]
	public function getRemoteKey(string $origin, string $keyId): ?Key {
		return $this->delegate->getRemoteKey($origin, $keyId);
	}
}

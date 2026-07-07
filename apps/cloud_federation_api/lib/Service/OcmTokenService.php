<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Service;

use OC\Authentication\Exceptions\ExpiredTokenException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\WipeTokenException;
use OC\Authentication\Token\IProvider;
use OCA\CloudFederationAPI\Db\OcmTokenMap;
use OCA\CloudFederationAPI\Db\OcmTokenMapMapper;

/**
 * Revokes OCM access tokens together with their ocm_token_map rows, so a
 * removed or expired mapping never leaves an orphaned oc_authtoken entry.
 */
class OcmTokenService {
	public function __construct(
		private readonly OcmTokenMapMapper $mapper,
		private readonly IProvider $tokenProvider,
	) {
	}

	/**
	 * Revoke every access token whose mapping expired before $time.
	 */
	public function revokeExpired(int $time): void {
		foreach ($this->mapper->findExpired($time) as $mapping) {
			$this->revokeMapping($mapping);
		}
	}

	/**
	 * Revoke every access token issued for the given refresh token. Tolerates
	 * the duplicate rows a concurrent exchange can leave behind.
	 */
	public function revokeByRefreshToken(string $refreshToken): void {
		foreach ($this->mapper->findAllByRefreshToken($refreshToken) as $mapping) {
			$this->revokeMapping($mapping);
		}
	}

	private function revokeMapping(OcmTokenMap $mapping): void {
		$this->revokeAccessToken($mapping->getAccessTokenId());
		$this->mapper->delete($mapping);
	}

	/**
	 * Delete the access token from oc_authtoken. getTokenById throws for an
	 * expired token but still carries it, so the owner uid required by
	 * invalidateTokenById is recoverable.
	 */
	private function revokeAccessToken(int $accessTokenId): void {
		try {
			$token = $this->tokenProvider->getTokenById($accessTokenId);
		} catch (ExpiredTokenException|WipeTokenException $e) {
			$token = $e->getToken();
		} catch (InvalidTokenException) {
			// Access token already gone; nothing left to revoke.
			return;
		}
		$this->tokenProvider->invalidateTokenById($token->getUID(), $accessTokenId);
	}
}

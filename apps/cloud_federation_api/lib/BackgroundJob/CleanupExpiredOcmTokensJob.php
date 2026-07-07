<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\BackgroundJob;

use OC\Authentication\Exceptions\ExpiredTokenException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\WipeTokenException;
use OC\Authentication\Token\IProvider;
use OCA\CloudFederationAPI\Db\OcmTokenMapMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

/**
 * Periodically purge expired OCM access tokens.
 *
 * Each expired mapping is revoked in two steps: the access token is deleted
 * from oc_authtoken and only then is the ocm_token_map row removed. Dropping
 * the mapping first would orphan the access token, since nothing else records
 * which oc_authtoken id belongs to a given refresh token.
 */
class CleanupExpiredOcmTokensJob extends TimedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private readonly OcmTokenMapMapper $mapper,
		private readonly IProvider $tokenProvider,
	) {
		parent::__construct($timeFactory);

		$this->setInterval(6 * 60 * 60); // run every 6 hours
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	#[\Override]
	protected function run($argument): void {
		$now = $this->time->getTime();
		foreach ($this->mapper->findExpired($now) as $mapping) {
			$this->revokeAccessToken($mapping->getAccessTokenId());
			$this->mapper->delete($mapping);
		}
	}

	/**
	 * Delete the access token itself from oc_authtoken. getTokenById throws
	 * for an already-expired token but still carries it, so we can recover the
	 * owner uid required by invalidateTokenById.
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

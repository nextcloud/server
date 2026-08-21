<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Listener;

use OC\Authentication\Token\IProvider;
use OCA\CloudFederationAPI\Service\OcmTokenService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\IShare;

/**
 * When a federated share is removed, revoke the OCM access tokens it minted
 * and invalidate its refresh token immediately, instead of waiting up to six
 * hours for the expiry job — which cannot even find them once the share, and
 * with it the mapping's context, is gone.
 *
 * @template-implements IEventListener<ShareDeletedEvent>
 */
class ShareDeletedListener implements IEventListener {
	public function __construct(
		private readonly OcmTokenService $tokenService,
		private readonly IProvider $tokenProvider,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof ShareDeletedEvent) {
			return;
		}

		$share = $event->getShare();
		if (!in_array($share->getShareType(), [IShare::TYPE_REMOTE, IShare::TYPE_REMOTE_GROUP], true)) {
			return;
		}

		$refreshToken = $share->getToken();
		if ($refreshToken === null || $refreshToken === '') {
			return;
		}

		// Revoke the access tokens exchanged from this share's secret...
		$this->tokenService->revokeByRefreshToken($refreshToken);
		// ...and the refresh (permanent) token itself. invalidateToken is a
		// no-op when the token is already gone.
		$this->tokenProvider->invalidateToken($refreshToken);
	}
}

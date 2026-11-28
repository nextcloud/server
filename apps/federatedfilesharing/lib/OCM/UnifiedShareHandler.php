<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FederatedFileSharing\OCM;

use Exception;
use NCU\Sharing\Exception\ShareNotFoundException;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCA\Sharing\Federation\FederationSharingService;
use OCP\Federation\Exceptions\BadRequestException;
use OCP\IDBConnection;
use OCP\Share\Exceptions\ShareNotFound;
use Psr\Log\LoggerInterface;

/**
 * Handles OCM requests from remote recipients of unified shares
 */
class UnifiedShareHandler {
	public function __construct(
		private readonly ISharingManager $sharingManager,
		private readonly IDBConnection $connection,
		private readonly LoggerInterface $logger,
	) {
	}

	public function isUnifiedProviderId(string $providerId): bool {
		return FederationSharingService::parseProviderId($providerId) !== null;
	}

	/**
	 * Get the cloud ID of the remote recipient that the shared secret belongs to.
	 */
	public function getRemoteRecipientCloudId(string $sharedSecret): ?string {
		$recipient = $this->getShareAndRemoteRecipientBySecret($sharedSecret)[1] ?? null;

		return $recipient === null ? null : $recipient->value . '@' . $recipient->instance;
	}

	/**
	 * @return ?array{Share, ShareRecipient} the active share and its remote recipient that the shared secret belongs to
	 */
	public function getShareAndRemoteRecipientBySecret(string $sharedSecret): ?array {
		if (!$this->sharingManager->isApiEnabled()) {
			return null;
		}

		try {
			$this->connection->beginTransaction();
			$shareId = $this->sharingManager->getShareIdByRecipientSecret($sharedSecret);
			$result = $shareId === null ? null : $this->getShareAndRemoteRecipient($shareId, $sharedSecret);
			$this->connection->commit();
		} catch (Exception $exception) {
			$this->connection->rollBack();
			throw $exception;
		}

		return $result;
	}

	/**
	 * Only validates the notification for now. For legacy shares, an activity is also published
	 * for the owner in CloudFederationProviderFiles::shareAccepted() via executeAcceptShare().
	 *
	 * @see CloudFederationProviderFiles::executeAcceptShare()
	 * @throws BadRequestException
	 * @throws ShareNotFound
	 */
	public function shareAccepted(string $providerId, array $notification): array {
		try {
			$this->connection->beginTransaction();
			[$share, $recipient] = $this->getShareAndRemoteRecipientFromNotification($providerId, $notification);
			$this->connection->commit();
		} catch (Exception $exception) {
			$this->connection->rollBack();
			throw $exception;
		}

		$this->logger->info('Remote recipient {recipientId} accepted share {shareId}', ['shareId' => $share->id, 'recipientId' => $recipient->id]);

		return [];
	}

	/**
	 * Declining any source removes the recipient from the whole share.
	 *
	 * @throws BadRequestException
	 * @throws ShareNotFound
	 */
	public function shareDeclined(string $providerId, array $notification): array {
		try {
			$this->connection->beginTransaction();
			[$share, $recipient] = $this->getShareAndRemoteRecipientFromNotification($providerId, $notification);
			$this->sharingManager->removeShareRecipient(new ShareAccessContext(overrideChecks: true), $share, $recipient);
			$this->connection->commit();
		} catch (Exception $exception) {
			$this->connection->rollBack();
			throw $exception;
		}

		$this->logger->info('Remote recipient {recipientId} declined share {shareId}', ['shareId' => $share->id, 'recipientId' => $recipient->id]);

		return [];
	}

	/**
	 * @return array{Share, ShareRecipient}
	 * @throws BadRequestException
	 * @throws ShareNotFound
	 */
	private function getShareAndRemoteRecipientFromNotification(string $providerId, array $notification): array {
		if (!isset($notification['sharedSecret']) || !is_string($notification['sharedSecret'])) {
			throw new BadRequestException(['sharedSecret']);
		}

		if (!$this->sharingManager->isApiEnabled()) {
			throw new ShareNotFound();
		}

		$parsedProviderId = FederationSharingService::parseProviderId($providerId) ?? throw new ShareNotFound();

		[$share, $recipient] = $this->getShareAndRemoteRecipient($parsedProviderId['shareId'], $notification['sharedSecret']) ?? throw new ShareNotFound();
		if ($recipient->id !== $parsedProviderId['recipientId'] || !$this->hasSource($share, $parsedProviderId['sourceValue'])) {
			throw new ShareNotFound();
		}

		return [$share, $recipient];
	}

	/**
	 * @return ?array{Share, ShareRecipient} the active share and its remote recipient that the shared secret belongs to
	 */
	private function getShareAndRemoteRecipient(string $shareId, string $sharedSecret): ?array {
		try {
			// OCM requests have no user session, so the regular access checks would not find the share.
			// Access is verified below instead, by matching the shared secret of remote recipient.
			$share = $this->sharingManager->getShare(new ShareAccessContext(overrideChecks: true), $shareId);
		} catch (ShareNotFoundException) {
			return null;
		}

		if ($share->state !== ShareState::Active) {
			return null;
		}

		foreach ($share->recipients as $recipient) {
			if ($recipient->instance !== null && $recipient->secret !== null && hash_equals($recipient->secret, $sharedSecret)) {
				return [$share, $recipient];
			}
		}

		return null;
	}

	private function hasSource(Share $share, string $sourceValue): bool {
		foreach ($share->sources as $source) {
			if ($source->class === NodeShareSourceType::class && $source->value === $sourceValue) {
				return true;
			}
		}

		return false;
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sharing\Federation;

use InvalidArgumentException;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Permission\ISharePermissionType;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareUser;
use NCU\Sharing\Source\ShareSource;
use OC\Core\Sharing\Permission\ReshareSharePermissionType;
use OC\Core\Sharing\Recipient\UserShareRecipientType;
use OCA\Files\Sharing\Permission\NodeCreateSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDeleteSharePermissionType;
use OCA\Files\Sharing\Permission\NodeReadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeUpdateSharePermissionType;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCP\AppFramework\Http;
use OCP\Constants;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudIdManager;
use OCP\IUserManager;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;

final readonly class FederationSharingService {
	public const string PROVIDER_ID_PREFIX = 'unified';

	public const string RESOURCE_TYPE_FILE = 'file';

	private const array SHARE_TYPES = [
		UserShareRecipientType::class => 'user',
		// GroupShareRecipientType::class => 'group',
	];

	/**
	 * Map ISharePermissionType classes to the permission bitmask
	 * expected by ICloudFederationFactory::getCloudFederationShare()
	 *
	 * @var array<class-string<ISharePermissionType>, int>
	 */
	private const array PERMISSIONS = [
		NodeReadSharePermissionType::class => Constants::PERMISSION_READ,
		NodeUpdateSharePermissionType::class => Constants::PERMISSION_UPDATE,
		NodeCreateSharePermissionType::class => Constants::PERMISSION_CREATE,
		NodeDeleteSharePermissionType::class => Constants::PERMISSION_DELETE,
		ReshareSharePermissionType::class => Constants::PERMISSION_SHARE,
	];

	public function __construct(
		private ICloudFederationFactory $federationFactory,
		private ICloudFederationProviderManager $federationProviderManager,
		private ICloudIdManager $cloudIdManager,
		private ISharingRegistry $registry,
		private IUserManager $userManager,
		private IManager $legacyManager,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Sends one OCM share per source to the remote recipient's OCM /shares endpoint
	 */
	public function sendShare(Share $share, ShareRecipient $recipient): void {
		if ($recipient->instance === null || $recipient->secret === null || !$recipient->initiator instanceof ShareUser || $recipient->id === null) {
			return;
		}

		$shareType = self::SHARE_TYPES[$recipient->class] ?? null;
		if ($shareType === null) {
			$this->logger->debug('Recipient type {class} can not be shared with remotely', ['class' => $recipient->class]);
			return;
		}

		if (!$this->isOutgoingSharingAllowed($shareType)) {
			$this->logger->debug('Outgoing federated {shareType} shares are disabled', ['shareType' => $shareType]);
			return;
		}

		$permissions = $this->getPermissions($share, $recipient);
		foreach ($share->sources as $source) {
			if ($source->class !== NodeShareSourceType::class) {
				continue;
			}

			$federationShare = $this->federationFactory->getCloudFederationShare(
				$recipient->value . '@' . $recipient->instance,
				$this->getResourceName($source),
				'',
				self::getProviderId($share->id, $recipient->id, $source->value),
				$this->getCloudId($share->owner),
				$this->getDisplayName($share->owner),
				$this->getCloudId($recipient->initiator),
				$this->getDisplayName($recipient->initiator),
				$recipient->secret,
				$shareType,
				self::RESOURCE_TYPE_FILE,
				$permissions,
			);

			$this->send($federationShare);
		}
	}

	/**
	 * Format: unified:{shareId}:{recipientId}:{sourceValue}
	 */
	public static function getProviderId(string $shareId, string $recipientId, string $sourceValue): string {
		return self::PROVIDER_ID_PREFIX . ':' . $shareId . ':' . $recipientId . ':' . $sourceValue;
	}

	private function send(ICloudFederationShare $federationShare): void {
		$context = [
			'providerId' => $federationShare->getProviderId(),
			'recipient' => $federationShare->getShareWith(),
		];

		try {
			$response = $this->federationProviderManager->sendCloudShare($federationShare);
		} catch (OCMProviderException|InvalidArgumentException $e) {
			$this->logger->warning('Failed to send share {providerId} to {recipient}', $context + ['exception' => $e]);
			return;
		}

		$context['status'] = $response->getStatusCode();
		$context['body'] = $response->getBody();
		if ($response->getStatusCode() === Http::STATUS_CREATED) {
			$this->logger->info('Sent share {providerId} to {recipient}', $context);
		} else {
			$this->logger->warning('Remote rejected share {providerId} for {recipient} with status {status}', $context);
		}
	}

	private function isOutgoingSharingAllowed(string $shareType): bool {
		return match ($shareType) {
			'user' => $this->legacyManager->outgoingServer2ServerSharesAllowed(),
			'group' => $this->legacyManager->outgoingServer2ServerGroupSharesAllowed(),
		};
	}

	private function getResourceName(ShareSource $source): string {
		return ($this->registry->getSourceTypes()[$source->class] ?? null)?->getSourceMetadata($source->value)?->getDisplayName() ?? $source->value;
	}

	private function getCloudId(ShareUser $user): string {
		return $this->cloudIdManager->getCloudId($user->userId, $user->instance)->getId();
	}

	private function getDisplayName(ShareUser $user): string {
		if ($user->instance !== null) {
			return $user->userId;
		}

		return $this->userManager->getDisplayName($user->userId) ?? $user->userId;
	}

	private function getPermissions(Share $share, ShareRecipient $recipient): int {
		$disabledPermissions = $recipient->getDisabledPermissions();

		$permissions = 0;
		foreach ($share->permissions as $permission) {
			if ($permission->enabled && !isset($disabledPermissions[$permission->class])) {
				$permissions |= self::PERMISSIONS[$permission->class] ?? 0;
			}
		}

		return $permissions;
	}
}

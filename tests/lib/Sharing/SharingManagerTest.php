<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Permission\SharePermissionPreset;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareState;
use OCP\Sharing\Source\ShareSource;
use PHPUnit\Framework\Attributes\Group;

#[Group(name: 'DB')]
final class SharingManagerTest extends AbstractSharingManagerTests {
	#[\Override]
	protected function searchRecipients(ShareAccessContext $accessContext, ?array $recipientTypeClasses, string $query, int $limit, int $offset): array {
		/** @psalm-suppress ArgumentTypeCoercion */
		return ShareRecipient::formatMultiple($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class), $this->manager->searchRecipients($accessContext, $recipientTypeClasses, $query, $limit, $offset));
	}

	#[\Override]
	protected function createShare(ShareAccessContext $accessContext): array {
		$id = $this->manager->createShare($accessContext);
		return $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
	}

	#[\Override]
	protected function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): array {
		$this->manager->updateShareState($accessContext, $id, $state);
		return $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
	}

	#[\Override]
	protected function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): array {
		$this->manager->addShareSource($accessContext, $id, $source);
		return $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
	}

	#[\Override]
	protected function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): array {
		$this->manager->removeShareSource($accessContext, $id, $source);
		return $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
	}

	#[\Override]
	protected function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): array {
		$this->manager->addShareRecipient($accessContext, $id, $recipient);
		return $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
	}

	#[\Override]
	protected function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): array {
		$this->manager->removeShareRecipient($accessContext, $id, $recipient);
		return $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
	}

	#[\Override]
	protected function updateShareRecipientSecret(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient, string $secret): array {
		$this->manager->updateShareRecipientSecret($accessContext, $id, $recipient, $secret);
		return $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
	}

	#[\Override]
	protected function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): array {
		$this->manager->updateShareProperty($accessContext, $id, $property);
		return $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
	}

	#[\Override]
	protected function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): array {
		$this->manager->updateSharePermission($accessContext, $id, $permission);
		return $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
	}

	#[\Override]
	protected function selectSharePermissionPreset(ShareAccessContext $accessContext, string $id, SharePermissionPreset $permissionPreset): array {
		$this->manager->selectSharePermissionPreset($accessContext, $id, $permissionPreset);
		return $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
	}

	#[\Override]
	protected function deleteShare(ShareAccessContext $accessContext, string $id): void {
		$this->manager->deleteShare($accessContext, $id);
	}

	#[\Override]
	protected function getShare(ShareAccessContext $accessContext, string $id): array {
		return $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
	}

	/**
	 * @return mixed[][]
	 */
	#[\Override]
	protected function listShares(ShareAccessContext $accessContext, ?string $sourceTypeClass, ?string $lastShareID, ?int $limit): array {
		/** @psalm-suppress ArgumentTypeCoercion */
		return array_map(fn (Share $share): array => $share->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class)), $this->manager->listShares($accessContext, $sourceTypeClass, $lastShareID, $limit));
	}
}

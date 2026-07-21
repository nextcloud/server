<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use Exception;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Permission\SharePermission;
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
		try {
			$this->dbConnection->beginTransaction();
			$id = $this->manager->createShare($accessContext);
			$share = $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[
		\Override]
	protected function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): array {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->updateShareState($accessContext, $id, $state);
			$share = $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): array {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->addShareSource($accessContext, $id, $source);
			$share = $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): array {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->removeShareSource($accessContext, $id, $source);
			$share = $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): array {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->addShareRecipient($accessContext, $id, $recipient);
			$share = $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): array {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->removeShareRecipient($accessContext, $id, $recipient);
			$share = $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function updateShareRecipientSecret(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient, string $secret): array {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->updateShareRecipientSecret($accessContext, $id, $recipient, $secret);
			$share = $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): array {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->updateShareProperty($accessContext, $id, $property);
			$share = $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): array {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->updateSharePermission($accessContext, $id, $permission);
			$share = $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function selectSharePermissionPreset(ShareAccessContext $accessContext, string $id, string $permissionPresetClass): array {
		try {
			$this->dbConnection->beginTransaction();
			/** @psalm-suppress ArgumentTypeCoercion */
			$this->manager->selectSharePermissionPreset($accessContext, $id, $permissionPresetClass);
			$share = $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function deleteShare(ShareAccessContext $accessContext, string $id): void {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->deleteShare($accessContext, $id);
			$this->dbConnection->commit();
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function getShare(ShareAccessContext $accessContext, string $id): array {
		try {
			$this->dbConnection->beginTransaction();
			$share = $this->manager->getShare($accessContext, $id)->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	/**
	 * @return mixed[][]
	 */
	#[\Override]
	protected function getShares(ShareAccessContext $accessContext, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, ?int $limit): array {
		try {
			$this->dbConnection->beginTransaction();
			/** @psalm-suppress ArgumentTypeCoercion */
			$shares = $this->manager->getShares($accessContext, $filterSourceTypeClass, $filterSourceTypeValue, $lastShareID, $limit);
			$this->dbConnection->commit();
			return Share::formatMultiple($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class), $shares);
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}
}

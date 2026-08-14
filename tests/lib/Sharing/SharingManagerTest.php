<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use Exception;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\Source\ShareSource;
use OC\Sharing\SharingManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;

#[Group(name: 'DB')]
final class SharingManagerTest extends AbstractSharingManagerTests {
	private function assertShareSyncedWithDb(ShareAccessContext $accessContext, Share $share): void {
		$retrieved = $this->manager->getShare($accessContext, $share->id);
		// don't compare time at sub-ms accuracy
		$this->assertEquals(SharingManager::timeToMs($retrieved->lastUpdated), SharingManager::timeToMs($share->lastUpdated));

		// now we compared the lastUpdated, make them the same to not fail the full comparison when there is a sub-ms lastUpdate difference
		$retrieved = new Share(
			$retrieved->id,
			$retrieved->owner,
			$share->lastUpdated,
			$retrieved->state,
			$retrieved->sources,
			$retrieved->recipients,
			$retrieved->properties,
			$retrieved->permissions
		);

		// cache the enabled permissions for both
		$retrieved->getEnabledPermissions();

		$share->getEnabledPermissions();
		// ensure source metadata is loaded
		foreach ($share->sources as $source) {
			$source->format($this->registry, $this->l10nFactory, false);
		}

		$this->assertEquals($retrieved, $share, 'share object not in sync with database');
	}

	#[\Override]
	protected function searchRecipients(ShareAccessContext $accessContext, ?array $filterRecipientTypeClasses, string $query, int $limit, int $offset, ?Share $forShare = null): array {
		try {
			$this->dbConnection->beginTransaction();
			if ($forShare instanceof Share) {
				$this->assertShareSyncedWithDb($accessContext, $forShare);
			}

			/** @psalm-suppress ArgumentTypeCoercion */
			$recipients = ShareRecipient::formatMultiple($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class), $this->manager->searchRecipients($accessContext, $filterRecipientTypeClasses, $query, $limit, $offset, $forShare));
			$this->dbConnection->commit();
			return $recipients;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function createShare(ShareAccessContext $accessContext): array {
		try {
			$this->dbConnection->beginTransaction();
			$share = $this->manager->createShare($accessContext);
			$this->assertShareSyncedWithDb($accessContext, $share);
			$share = $share->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[
		\Override]
	protected function updateShareState(ShareAccessContext $accessContext, Share $share, ShareState $state): array {
		try {
			$this->dbConnection->beginTransaction();
			$share = $this->manager->updateShareState($accessContext, $share, $state);
			$this->assertShareSyncedWithDb($accessContext, $share);
			$share = $share->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function addShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): array {
		try {
			$this->dbConnection->beginTransaction();
			$share = $this->manager->addShareSource($accessContext, $share, $source);
			$this->assertShareSyncedWithDb($accessContext, $share);
			$share = $share->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function removeShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): array {
		try {
			$this->dbConnection->beginTransaction();
			$this->assertShareSyncedWithDb($accessContext, $share);
			$share = $this->manager->removeShareSource($accessContext, $share, $source);
			$this->assertShareSyncedWithDb($accessContext, $share);
			$share = $share->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function addShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): array {
		try {
			$this->dbConnection->beginTransaction();
			$share = $this->manager->addShareRecipient($accessContext, $share, $recipient);
			$this->assertShareSyncedWithDb($accessContext, $share);
			$share = $share->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function removeShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): array {
		try {
			$this->dbConnection->beginTransaction();
			$share = $this->manager->removeShareRecipient($accessContext, $share, $recipient);
			$this->assertShareSyncedWithDb($accessContext, $share);
			$share = $share->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function updateShareRecipientSecret(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient, string $secret): array {
		try {
			$this->dbConnection->beginTransaction();
			$share = $this->manager->updateShareRecipientSecret($accessContext, $share, $recipient, $secret);
			$this->assertShareSyncedWithDb($accessContext, $share);
			$share = $share->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function updateShareProperty(ShareAccessContext $accessContext, Share $share, ShareProperty $property): array {
		try {
			$this->dbConnection->beginTransaction();
			$share = $this->manager->updateShareProperty($accessContext, $share, $property);
			$this->assertShareSyncedWithDb($accessContext, $share);
			$share = $share->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function updateSharePermission(ShareAccessContext $accessContext, Share $share, SharePermission $permission): array {
		try {
			$this->dbConnection->beginTransaction();
			$share = $this->manager->updateSharePermission($accessContext, $share, $permission);
			$this->assertShareSyncedWithDb($accessContext, $share);
			$share = $share->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function selectSharePermissionPreset(ShareAccessContext $accessContext, Share $share, string $permissionPresetClass): array {
		try {
			$this->dbConnection->beginTransaction();
			/** @psalm-suppress ArgumentTypeCoercion */
			$share = $this->manager->selectSharePermissionPreset($accessContext, $share, $permissionPresetClass);
			$this->assertShareSyncedWithDb($accessContext, $share);
			$share = $share->format($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class));
			$this->dbConnection->commit();
			return $share;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	#[\Override]
	protected function deleteShare(ShareAccessContext $accessContext, Share $share): void {
		try {
			$this->dbConnection->beginTransaction();
			$this->assertShareSyncedWithDb($accessContext, $share);
			$this->manager->deleteShare($accessContext, $share);
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

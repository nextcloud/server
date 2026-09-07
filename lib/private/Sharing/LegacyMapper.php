<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Sharing;

use DateTimeImmutable;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

final readonly class LegacyMapper {
	public function __construct(
		private IDBConnection $dbConnection,
	) {
	}

	public function createLegacyMapping(string $id, string $legacyProvider, int $legacyId, DateTimeImmutable $lastUpdated, string $secret): LegacyMapping {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->insert('sharing_share_legacy_mapping')
			->values([
				'id' => $qb->createNamedParameter($id),
				'legacy_provider' => $qb->createNamedParameter($legacyProvider),
				'legacy_id' => $qb->createNamedParameter($legacyId, IQueryBuilder::PARAM_INT),
				'last_updated' => $qb->createNamedParameter(SharingManager::timeToMs($lastUpdated)),
				'secret' => $qb->createNamedParameter($secret),
			])
			->executeStatement();

		return new LegacyMapping(
			$id,
			$legacyProvider,
			$legacyId,
			$lastUpdated,
			$secret,
		);
	}

	public function getLegacyMappingByLegacyProviderAndId(string $legacyProvider, int $legacyId): ?LegacyMapping {
		$qb = $this->dbConnection->getQueryBuilder();
		$result = $qb
			->select('id', 'last_updated', 'secret')
			->from('sharing_share_legacy_mapping')
			->where($qb->expr()->eq('legacy_provider', $qb->createNamedParameter($legacyProvider)))
			->andWhere($qb->expr()->eq('legacy_id', $qb->createNamedParameter($legacyId, IQueryBuilder::PARAM_INT)))
			->executeQuery();

		/** @var array{id: int, last_updated: int, secret: string}|false $row */
		$row = $result->fetch();
		if ($row === false) {
			return null;
		}

		return new LegacyMapping(
			(string)$row['id'],
			$legacyProvider,
			$legacyId,
			SharingBackend::parseTimestamp((string)$row['last_updated']),
			$row['secret'],
		);
	}

	/**
	 * @return list<LegacyMapping>
	 */
	public function getLegacyMappings(string $id): array {
		$legacyMappings = [];

		$qb = $this->dbConnection->getQueryBuilder();
		$result = $qb
			->select('id', 'legacy_provider', 'legacy_id', 'last_updated', 'secret')
			->from('sharing_share_legacy_mapping')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeQuery();

		/** @var list<array{id: int, legacy_provider: string, legacy_id: int, last_updated: int, secret: string}> $rows */
		$rows = $result->fetchAllAssociative();
		foreach ($rows as $row) {
			$legacyMappings[] = new LegacyMapping(
				(string)$row['id'],
				$row['legacy_provider'],
				$row['legacy_id'],
				SharingBackend::parseTimestamp((string)$row['last_updated']),
				$row['secret'],
			);
		}

		return $legacyMappings;
	}

	public function deleteLegacyMappings(string $id): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->delete('sharing_share_legacy_mapping')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeStatement();
	}

	public function updateLegacyMapping(LegacyMapping $legacyMapping): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->update('sharing_share_legacy_mapping')
			->set('last_updated', $qb->createNamedParameter(SharingManager::timeToMs($legacyMapping->lastUpdated)))
			->set('secret', $qb->createNamedParameter($legacyMapping->secret))
			->where($qb->expr()->eq('legacy_provider', $qb->createNamedParameter($legacyMapping->legacyProvider)))
			->andWhere($qb->expr()->eq('legacy_id', $qb->createNamedParameter($legacyMapping->legacyId)))
			->executeStatement();
	}
}

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
use RuntimeException;

// TODO: Use the ORM system
final readonly class LegacyMapper {
	public function __construct(
		private IDBConnection $dbConnection,
	) {
	}

	/**
	 * @param non-empty-string $id
	 * @param non-empty-list<non-empty-string> $recipientSecrets
	 */
	public function createLegacyMapping(string $id, string $legacyProvider, int $legacyId, DateTimeImmutable $lastUpdated, array $recipientSecrets, string $recipientValue): LegacyMapping {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->insert('sharing_share_legacy_mapping')
			->values([
				'id' => $qb->createNamedParameter($id),
				'legacy_provider' => $qb->createNamedParameter($legacyProvider),
				'legacy_id' => $qb->createNamedParameter($legacyId, IQueryBuilder::PARAM_INT),
				'last_updated' => $qb->createNamedParameter(SharingManager::timeToMs($lastUpdated)),
				'recipient_secrets' => $qb->createNamedParameter(json_encode($recipientSecrets)),
				'recipient_value' => $qb->createNamedParameter($recipientValue),
			])
			->executeStatement();

		return new LegacyMapping(
			$id,
			$legacyProvider,
			$legacyId,
			$lastUpdated,
			$recipientSecrets,
			$recipientValue,
		);
	}

	public function getLegacyMappingByLegacyProviderAndId(string $legacyProvider, int $legacyId): ?LegacyMapping {
		$qb = $this->dbConnection->getQueryBuilder();
		$result = $qb
			->select('id', 'last_updated', 'recipient_secrets', 'recipient_value')
			->from('sharing_share_legacy_mapping')
			->where($qb->expr()->eq('legacy_provider', $qb->createNamedParameter($legacyProvider)))
			->andWhere($qb->expr()->eq('legacy_id', $qb->createNamedParameter($legacyId, IQueryBuilder::PARAM_INT)))
			->executeQuery();

		/** @var array{id: int, last_updated: int, recipient_secrets: non-empty-string, recipient_value: string}|false $row */
		$row = $result->fetch();
		if ($row === false) {
			return null;
		}

		return new LegacyMapping(
			(string)$row['id'],
			$legacyProvider,
			$legacyId,
			SharingBackend::parseTimestamp((string)$row['last_updated']),
			$this->decodeRecipientSecrets($row['recipient_secrets']),
			$row['recipient_value'],
		);
	}

	/**
	 * @return list<LegacyMapping>
	 */
	public function getLegacyMappings(string $id): array {
		$legacyMappings = [];

		$qb = $this->dbConnection->getQueryBuilder();
		$result = $qb
			->select('id', 'legacy_provider', 'legacy_id', 'last_updated', 'recipient_secrets', 'recipient_value')
			->from('sharing_share_legacy_mapping')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeQuery();

		/** @var list<array{id: int, legacy_provider: string, legacy_id: int, last_updated: int, recipient_secrets: non-empty-string, recipient_value: string}> $rows */
		$rows = $result->fetchAllAssociative();
		foreach ($rows as $row) {
			$legacyMappings[] = new LegacyMapping(
				(string)$row['id'],
				$row['legacy_provider'],
				$row['legacy_id'],
				SharingBackend::parseTimestamp((string)$row['last_updated']),
				$this->decodeRecipientSecrets($row['recipient_secrets']),
				$row['recipient_value'],
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
			->set('recipient_secrets', $qb->createNamedParameter(json_encode($legacyMapping->recipientSecrets)))
			->set('recipient_value', $qb->createNamedParameter($legacyMapping->recipientValue))
			->where($qb->expr()->eq('legacy_provider', $qb->createNamedParameter($legacyMapping->legacyProvider)))
			->andWhere($qb->expr()->eq('legacy_id', $qb->createNamedParameter($legacyMapping->legacyId)))
			->executeStatement();
	}

	/**
	 * @psalm-suppress MixedReturnTypeCoercion Psalm doesn't understand the string checks
	 * @param non-empty-string $raw
	 * @return non-empty-list<non-empty-string>
	 */
	private function decodeRecipientSecrets(string $raw): array {
		$data = json_decode($raw);
		if (!is_array($data) || !array_is_list($data) || $data === [] || array_any($data, static fn (mixed $value): bool => !is_string($value) || $value === '')) {
			throw new RuntimeException('The recipient secrets must be a non-empty list of non-empty strings.');
		}

		return $data;
	}
}

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
use OCP\Share\IShareProvider;
use RuntimeException;

// TODO: Use the ORM system
final readonly class LegacyMapper {
	public function __construct(
		private IDBConnection $dbConnection,
		private ClassMapper $classMapper,
	) {
	}

	/**
	 * @param non-empty-string $id
	 * @param class-string<IShareProvider> $legacyProviderClass
	 * @param non-empty-list<non-empty-string> $recipientSecrets
	 */
	public function createLegacyMapping(string $id, string $legacyProviderClass, int $legacyId, DateTimeImmutable $lastUpdated, array $recipientSecrets, string $recipientValue): LegacyMapping {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->insert('sharing_share_legacy_mapping')
			->values([
				'id' => $qb->createNamedParameter($id),
				'legacy_provider_class_id' => $qb->createNamedParameter($this->classMapper->getClassId($legacyProviderClass), IQueryBuilder::PARAM_INT),
				'legacy_id' => $qb->createNamedParameter($legacyId, IQueryBuilder::PARAM_INT),
				'last_updated' => $qb->createNamedParameter(SharingManager::timeToMs($lastUpdated)),
				'recipient_secrets' => $qb->createNamedParameter(json_encode($recipientSecrets)),
				'recipient_value' => $qb->createNamedParameter($recipientValue),
			])
			->executeStatement();

		return new LegacyMapping(
			$id,
			$legacyProviderClass,
			$legacyId,
			$lastUpdated,
			$recipientSecrets,
			$recipientValue,
		);
	}

	/**
	 * @param class-string<IShareProvider> $legacyProviderClass
	 */
	public function getLegacyMappingByLegacyProviderClassAndId(string $legacyProviderClass, int $legacyId): ?LegacyMapping {
		$qb = $this->dbConnection->getQueryBuilder();
		$result = $qb
			->select('id', 'last_updated', 'recipient_secrets', 'recipient_value')
			->from('sharing_share_legacy_mapping')
			->where($qb->expr()->eq('legacy_provider_class_id', $qb->createNamedParameter($this->classMapper->getClassId($legacyProviderClass), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('legacy_id', $qb->createNamedParameter($legacyId, IQueryBuilder::PARAM_INT)))
			->executeQuery();

		/** @var array{id: int, last_updated: int, recipient_secrets: non-empty-string, recipient_value: string}|false $row */
		$row = $result->fetch();
		if ($row === false) {
			return null;
		}

		return new LegacyMapping(
			(string)$row['id'],
			$legacyProviderClass,
			$legacyId,
			SharingBackend::parseTimestamp((string)$row['last_updated']),
			$this->decodeRecipientSecrets($row['recipient_secrets']),
			$row['recipient_value'],
		);
	}

	/**
	 * @param non-empty-string $id
	 * @return list<LegacyMapping>
	 */
	public function getLegacyMappings(string $id): array {
		$legacyMappings = [];

		$qb = $this->dbConnection->getQueryBuilder();
		$result = $qb
			->select('id', 'legacy_provider_class_id', 'legacy_id', 'last_updated', 'recipient_secrets', 'recipient_value')
			->from('sharing_share_legacy_mapping')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeQuery();

		/** @var list<array{id: int, legacy_provider_class_id: int|string, legacy_id: int|string, last_updated: int, recipient_secrets: non-empty-string, recipient_value: string}> $rows */
		$rows = $result->fetchAllAssociative();
		foreach ($rows as $row) {
			/** @var class-string<IShareProvider> $legacyProviderClass */
			$legacyProviderClass = $this->classMapper->getClassName((int)$row['legacy_provider_class_id']);
			$legacyMappings[] = new LegacyMapping(
				(string)$row['id'],
				$legacyProviderClass,
				(int)$row['legacy_id'],
				SharingBackend::parseTimestamp((string)$row['last_updated']),
				$this->decodeRecipientSecrets($row['recipient_secrets']),
				$row['recipient_value'],
			);
		}

		return $legacyMappings;
	}

	/**
	 * @param non-empty-string $id
	 */
	public function deleteLegacyMappings(string $id): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->delete('sharing_share_legacy_mapping')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeStatement();
	}

	/**
	 * @param class-string<IShareProvider> $legacyProviderClass
	 */
	public function deleteLegacyMapping(string $legacyProviderClass, int $legacyId): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->delete('sharing_share_legacy_mapping')
			->where($qb->expr()->eq('legacy_provider_class_id', $qb->createNamedParameter($this->classMapper->getClassId($legacyProviderClass), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('legacy_id', $qb->createNamedParameter($legacyId, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	public function updateLegacyMapping(LegacyMapping $legacyMapping): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->update('sharing_share_legacy_mapping')
			->set('last_updated', $qb->createNamedParameter(SharingManager::timeToMs($legacyMapping->lastUpdated)))
			->set('recipient_secrets', $qb->createNamedParameter(json_encode($legacyMapping->recipientSecrets)))
			->set('recipient_value', $qb->createNamedParameter($legacyMapping->recipientValue))
			->where($qb->expr()->eq('legacy_provider_class_id', $qb->createNamedParameter($this->classMapper->getClassId($legacyMapping->legacyProviderClass), IQueryBuilder::PARAM_INT)))
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

<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing;

use OCA\Sharing\Exception\ShareInvalidException;
use OCA\Sharing\Exception\ShareInvalidRecipientSearchParametersException;
use OCA\Sharing\Exception\ShareNotFoundException;
use OCA\Sharing\Exception\ShareOperationNotAllowedException;
use OCA\Sharing\Model\AShareRecipientType;
use OCA\Sharing\Model\AShareSourceType;
use OCA\Sharing\Model\IShareFeatureFilter;
use OCA\Sharing\Model\Share;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use RuntimeException;

class Manager {
	public function __construct(
		private readonly IDBConnection $connection,
		private readonly Registry $registry,
	) {
	}

	/**
	 * @param class-string<AShareRecipientType> $recipientType
	 * @param non-empty-string $query
	 * @param int<1, 100> $limit
	 * @param non-negative-int $offset
	 * @return list<string>
	 * @throws ShareInvalidRecipientSearchParametersException
	 */
	public function searchRecipients(string $recipientType, string $query, int $limit, int $offset): array {
		/** @psalm-suppress TypeDoesNotContainType */
		if ($query === '') {
			throw new ShareInvalidRecipientSearchParametersException('The query is empty.');
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($limit < 1) {
			throw new ShareInvalidRecipientSearchParametersException('The limit is too low.');
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($limit > 100) {
			throw new ShareInvalidRecipientSearchParametersException('The limit is too high.');
		}

		/** @psalm-suppress DocblockTypeContradiction */
		if ($offset < 0) {
			throw new ShareInvalidRecipientSearchParametersException('The offset is too low.');
		}

		$recipientTypes = $this->registry->getRecipientTypes();
		if (!isset($recipientTypes[$recipientType])) {
			throw new ShareInvalidRecipientSearchParametersException('The recipient type is not registered.');
		}

		return $recipientTypes[$recipientType]->searchRecipients($query, $limit, $offset);
	}

	/**
	 * @throws ShareInvalidException
	 */
	private function validate(Share $share): void {
		/** {@see \OC\Snowflake\Decoder::decode} */
		if (!ctype_digit($share->id)) {
			throw new ShareInvalidException('The ID is not a valid Snowflake ID.');
		}

		$creator = Server::get(IUserManager::class)->get($share->creator);
		if ($creator === null) {
			throw new ShareInvalidException('The creator does not exist.');
		}

		if ($share->sources === []) {
			throw new ShareInvalidException('The sources are missing.');
		}

		$sourceTypes = $this->registry->getSourceTypes();
		if (!isset($sourceTypes[$share->sourceType])) {
			throw new ShareInvalidException('The source type is not registered.');
		}

		$sourceType = $sourceTypes[$share->sourceType];
		foreach ($share->sources as $source) {
			if (!$sourceType->validateSource($creator, $source)) {
				throw new ShareInvalidException('The source ' . $source . ' is not valid.');
			}
		}

		if ($share->recipients === []) {
			throw new ShareInvalidException('The recipients are missing.');
		}

		$recipientTypes = $this->registry->getRecipientTypes();
		if (!isset($recipientTypes[$share->recipientType])) {
			throw new ShareInvalidException('The recipient type is not registered.');
		}

		$recipientType = $recipientTypes[$share->recipientType];
		foreach ($share->recipients as $recipient) {
			if (!$recipientType->validateRecipient($creator, $recipient)) {
				throw new ShareInvalidException('The recipient ' . $recipient . ' is not valid.');
			}
		}

		$features = $this->registry->getFeatures();
		foreach ($share->properties as $feature => $properties) {
			if (!isset($features[$feature])) {
				throw new ShareInvalidException('The feature is not registered.');
			}

			if (!$features[$feature]->validateProperties($properties)) {
				throw new ShareInvalidException('The properties for feature ' . $feature . ' are not valid.');
			}
		}
	}

	/**
	 * @throws ShareOperationNotAllowedException
	 */
	private function validateShareOperation(?IUser $user, Share $share): void {
		if ($user instanceof IUser && $share->creator !== $user->getUID()) {
			throw new ShareOperationNotAllowedException();
		}
	}

	/**
	 * @throws ShareOperationNotAllowedException
	 * @throws ShareInvalidException
	 */
	public function insert(?IUser $user, Share $share): void {
		$this->validateShareOperation($user, $share);
		$this->validate($share);

		$this->connection->beginTransaction();

		$qb = $this->connection->getQueryBuilder();
		$qb
			->insert('sharing_share')
			->values([
				'id' => $qb->createNamedParameter($share->id),
				'creator' => $qb->createNamedParameter($share->creator),
				'source_type' => $qb->createNamedParameter($share->sourceType),
				'recipient_type' => $qb->createNamedParameter($share->recipientType),
			])
			->executeStatement();

		$this->insertSources($share);
		$this->insertRecipients($share);
		$this->insertProperties($share);

		try {
			$this->connection->commit();
		} catch (Exception $exception) {
			$this->connection->rollBack();
			throw $exception;
		}
	}

	private function insertSources(Share $share): void {
		$qb = $this->connection->getQueryBuilder()
			->insert('sharing_share_sources');
		foreach ($share->sources as $source) {
			$qb
				->values([
					'share_id' => $qb->createNamedParameter($share->id),
					'source' => $qb->createNamedParameter($source),
				])
				->executeStatement();
		}
	}

	private function insertRecipients(Share $share): void {
		$qb = $this->connection->getQueryBuilder()
			->insert('sharing_share_recipients');
		foreach ($share->recipients as $recipient) {
			$qb
				->values([
					'share_id' => $qb->createNamedParameter($share->id),
					'recipient' => $qb->createNamedParameter($recipient),
				])
				->executeStatement();
		}
	}

	private function insertProperties(Share $share): void {
		$qb = $this->connection->getQueryBuilder()
			->insert('sharing_share_properties');
		foreach ($share->properties as $feature => $properties) {
			foreach ($properties as $key => $values) {
				foreach ($values as $value) {
					$qb
						->values([
							'share_id' => $qb->createNamedParameter($share->id),
							'feature' => $qb->createNamedParameter($feature),
							'key' => $qb->createNamedParameter($key),
							'value' => $qb->createNamedParameter($value),
						])
						->executeStatement();
				}
			}
		}
	}

	/**
	 * @throws ShareNotFoundException
	 * @throws ShareInvalidException
	 * @throws ShareOperationNotAllowedException
	 */
	public function update(?IUser $user, Share $share, bool $applySelectors = true, bool $applyFilters = true): void {
		$originalShare = $this->get($user, $share->id, $applySelectors, $applyFilters);
		$this->validateShareOperation($user, $originalShare);
		$this->validate($share);

		if ($originalShare->creator !== $share->creator) {
			throw new ShareInvalidException('The creator cannot be updated.');
		}

		if ($originalShare->sourceType !== $share->sourceType) {
			throw new ShareInvalidException('The sourceType cannot be updated.');
		}

		if ($originalShare->recipientType !== $share->recipientType) {
			throw new ShareInvalidException('The recipientType cannot be updated.');
		}

		$this->connection->beginTransaction();

		$this->deleteSources($share->id);
		$this->insertSources($share);

		$this->deleteRecipients($share->id);
		$this->insertRecipients($share);

		$this->deleteProperties($share->id);
		$this->insertProperties($share);

		try {
			$this->connection->commit();
		} catch (Exception $exception) {
			$this->connection->rollBack();
			throw $exception;
		}
	}

	/**
	 * @throws ShareNotFoundException
	 * @throws ShareOperationNotAllowedException
	 */
	public function delete(?IUser $user, string $shareID, bool $applySelectors = true, bool $applyFilters = true): void {
		$originalShare = $this->get($user, $shareID, $applySelectors, $applyFilters);
		$this->validateShareOperation($user, $originalShare);

		$this->connection->beginTransaction();

		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($shareID)))
			->executeStatement();

		$this->deleteSources($shareID);
		$this->deleteRecipients($shareID);
		$this->deleteProperties($shareID);

		try {
			$this->connection->commit();
		} catch (Exception $exception) {
			$this->connection->rollBack();
			throw $exception;
		}
	}

	private function deleteSources(string $shareID): void {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share_sources')
			->where($qb->expr()->eq('share_id', $qb->createNamedParameter($shareID)))
			->executeStatement();
	}

	private function deleteRecipients(string $shareID): void {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share_recipients')
			->where($qb->expr()->eq('share_id', $qb->createNamedParameter($shareID)))
			->executeStatement();
	}

	private function deleteProperties(string $shareID): void {
		$qb = $this->connection->getQueryBuilder();
		$qb
			->delete('sharing_share_properties')
			->where($qb->expr()->eq('share_id', $qb->createNamedParameter($shareID)))
			->executeStatement();
	}

	/**
	 * @throws ShareNotFoundException
	 */
	public function get(?IUser $currentUser, string $shareID, bool $applySelectors = true, bool $applyFilters = true): Share {
		$shares = $this->internalList($currentUser, $shareID, null, $applySelectors, $applyFilters);
		if (count($shares) !== 1) {
			throw new ShareNotFoundException($shareID);
		}

		return $shares[0];
	}

	/**
	 * @param ?class-string<AShareSourceType> $sourceType
	 * @return list<Share>
	 */
	public function list(?IUser $currentUser, ?string $sourceType, bool $applySelectors = true, bool $applyFilters = true): array {
		return $this->internalList($currentUser, null, $sourceType, $applySelectors, $applyFilters);
	}

	/**
	 * @param ?class-string<AShareSourceType> $sourceType
	 * @return list<Share>
	 */
	private function internalList(?IUser $currentUser, ?string $shareID, ?string $sourceType, bool $applySelectors = true, bool $applyFilters = true): array {
		$qb = $this->connection->getQueryBuilder();

		$qb
			->select('*')
			->from('sharing_share', 's')
			->leftJoin('s', 'sharing_share_sources', 'ss', $qb->expr()->eq('s.id', 'ss.share_id'))
			->leftJoin('s', 'sharing_share_recipients', 'sr', $qb->expr()->eq('s.id', 'sr.share_id'))
			->leftJoin('s', 'sharing_share_properties', 'sp', $qb->expr()->eq('s.id', 'sp.share_id'));

		if ($applySelectors) {
			if (!$currentUser instanceof IUser) {
				throw new RuntimeException('If selectors are applied, the current user must be supplied.');
			}

			$selectors = [$qb->expr()->eq('s.creator', $qb->createNamedParameter($currentUser->getUID()))];

			foreach ($this->registry->getRecipientTypes() as $recipientType) {
				$selectors[] = $qb->expr()->andX(
					$qb->expr()->eq('s.recipient_type', $qb->createNamedParameter($recipientType::class)),
					$qb->expr()->in('sr.recipient', $qb->createNamedParameter($recipientType->getRecipientValues($currentUser), IQueryBuilder::PARAM_STR_ARRAY)),
				);
			}

			$qb->andWhere($qb->expr()->orX(...$selectors));
		}

		$filters = [];

		if ($shareID !== null) {
			$filters[] = $qb->expr()->eq('s.id', $qb->createNamedParameter($shareID));
		}

		if ($sourceType !== null) {
			$filters[] = $qb->expr()->eq('s.source_type', $qb->createNamedParameter($sourceType));
		}

		if ($filters !== []) {
			$qb->andWhere($qb->expr()->andX(...$filters));
		}

		$shares = [];
		$result = $qb->executeQuery();
		/** @var array<string, mixed> $row */
		foreach ($result->fetchAll() as $row) {
			$id = (string)$row['id'];

			$shares[$id] ??= [
				'id' => $id,
				'creator' => (string)$row['creator'],
				'source_type' => (string)$row['source_type'],
				'sources' => [],
				'recipient_type' => (string)$row['recipient_type'],
				'recipients' => [],
				'properties' => [],
			];

			if ($row['source'] !== null) {
				$shares[$id]['sources'][] = (string)$row['source'];
			}

			if ($row['recipient'] !== null) {
				$shares[$id]['recipients'][] = (string)$row['recipient'];
			}

			if ($row['feature'] !== null) {
				$shares[$id]['properties'][(string)$row['feature']] ??= [];
				$shares[$id]['properties'][(string)$row['feature']][(string)$row['key']] ??= [];
				$shares[$id]['properties'][(string)$row['feature']][(string)$row['key']][] = (string)$row['value'];
			}
		}

		$result->closeCursor();

		/** @psalm-suppress ArgumentTypeCoercion */
		$shares = array_map(Share::fromArray(...), $shares);

		if ($applyFilters) {
			// TODO: Ideally the feature filter logic would be part of the query, but that would be quite complex due to the LEFT JOINs. Check if there is an easy and performant way to still do it in the query.
			$shares = array_filter($shares, function (Share $share): bool {
				foreach ($this->registry->getFeatures() as $feature) {
					if (!isset($share->properties[$feature::class])) {
						continue;
					}

					if (($feature instanceof IShareFeatureFilter) && $feature->isFiltered($share->properties[$feature::class])) {
						return false;
					}
				}

				return true;
			});
		}

		return array_values($shares);
	}
}

<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing;

use NCU\Sharing\ShareUser;
use OC\Files\Filesystem;
use OCA\Files_Sharing\AppInfo\Application;
use OCP\Config\IUserConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IDBConnection;
use RuntimeException;

final readonly class SourceNodeTargetManager {
	public function __construct(
		private IConfig $config,
		private IUserConfig $userConfig,
		private IDBConnection $dbConnection,
	) {
	}

	/**
	 * @param list<string> $userIds
	 * @return array<string, string>
	 */
	private function getTargetsInternal(array $userIds, ShareUser $owner, Node $sourceNode): array {
		$targets = [];

		foreach (array_chunk($userIds, 1000) as $chunk) {
			$qb = $this->dbConnection->getQueryBuilder();
			$result = $qb
				->select('user_id', 'target')
				->from('sharing_source_node_target')
				->where($qb->expr()->in('user_id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_STR_ARRAY)))
				->andWhere(
					$owner->instance === null
					? $qb->expr()->isNull('source_instance')
					: $qb->expr()->eq('source_instance', $qb->createNamedParameter($owner->instance))
				)
				->andWhere($qb->expr()->eq('source_node_id', $qb->createNamedParameter($sourceNode->getId(), IQueryBuilder::PARAM_INT)))
				->executeQuery();

			/** @var list<array{user_id: string, target: string}> $rows */
			$rows = $result->fetchAllAssociative();
			foreach ($rows as $row) {
				$targets[$row['user_id']] = $row['target'];
			}
		}

		return $targets;
	}

	/**
	 * @throws NotFoundException
	 */
	public function createDefaultTarget(string $userId, ShareUser $owner, Node $sourceNode): string {
		if ($owner->instance !== null) {
			throw new RuntimeException('Federation is not supported yet.');
		}

		$shareFolder = $this->config->getSystemValueString('share_folder', '/');
		if ($this->config->getSystemValueBool('sharing.allow_custom_share_folder', true)) {
			$shareFolder = $this->userConfig->getValueString($userId, Application::APP_ID, 'share_folder', $shareFolder);
		}

		$target = Filesystem::normalizePath($shareFolder . '/' . $sourceNode->getName());

		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->insert('sharing_source_node_target')
			->values([
				'user_id' => $qb->createNamedParameter($userId),
				'source_instance' => $qb->createNamedParameter($owner->instance),
				'source_node_id' => $qb->createNamedParameter($sourceNode->getId()),
				'target' => $qb->createNamedParameter($target),
			])
			->executeStatement();

		return $target;
	}

	public function getTarget(string $userId, ShareUser $owner, Node $sourceNode): ?string {
		return $this->getTargetsInternal([$userId], $owner, $sourceNode)[$userId] ?? null;
	}

	/**
	 * @param list<string> $userIds
	 * @return array<string, string>
	 */
	public function getTargets(array $userIds, ShareUser $owner, Node $sourceNode): array {
		$targets = $this->getTargetsInternal($userIds, $owner, $sourceNode);
		foreach ($userIds as $userId) {
			if (isset($targets[$userId])) {
				continue;
			}

			$targets[$userId] = $this->createDefaultTarget($userId, $owner, $sourceNode);
		}

		return $targets;
	}

	public function setTarget(string $userId, ShareUser $owner, Node $sourceNode, string $target): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$rowCount = $qb
			->update('sharing_source_node_target')
			->set('target', $qb->createNamedParameter($target))
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere(
				$owner->instance === null
				? $qb->expr()->isNull('source_instance')
				: $qb->expr()->eq('source_instance', $qb->createNamedParameter($owner->instance))
			)
			->andWhere($qb->expr()->eq('source_node_id', $qb->createNamedParameter($sourceNode->getId(), IQueryBuilder::PARAM_INT)))
			->executeStatement();

		if ($rowCount === 0) {
			$qb = $this->dbConnection->getQueryBuilder();
			$qb
				->insert('sharing_source_node_target')
				->values([
					'user_id' => $qb->createNamedParameter($userId),
					'source_instance' => $qb->createNamedParameter($owner->instance),
					'source_node_id' => $qb->createNamedParameter($sourceNode->getId()),
					'target' => $qb->createNamedParameter($target),
				])
				->executeStatement();
		}
	}
}

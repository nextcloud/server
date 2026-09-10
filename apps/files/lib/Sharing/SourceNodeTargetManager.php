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
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IDBConnection;
use RuntimeException;

final readonly class SourceNodeTargetManager {
	public function __construct(
		private IRootFolder $rootFolder,
		private IConfig $config,
		private IUserConfig $userConfig,
		private IDBConnection $dbConnection,
	) {
	}

	private function getTargetInternal(string $userId, ShareUser $owner, int $sourceNodeId): ?string {
		$qb = $this->dbConnection->getQueryBuilder();
		$result = $qb
			->select('target')
			->from('sharing_source_node_target')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere(
				$owner->instance === null
				? $qb->expr()->isNull('source_instance')
				: $qb->expr()->eq('source_instance', $qb->createNamedParameter($owner->instance))
			)
			->andWhere($qb->expr()->eq('source_node_id', $qb->createNamedParameter($sourceNodeId, IQueryBuilder::PARAM_INT)))
			->executeQuery();

		/** @var string|false $target */
		$target = $result->fetchOne();
		if ($target === false) {
			return null;
		}

		return $target;
	}

	/**
	 * @throws NotFoundException
	 */
	public function createDefaultTarget(string $userId, ShareUser $owner, int $sourceNodeId): string {
		if ($owner->instance !== null) {
			throw new RuntimeException('Federation is not supported yet.');
		}

		if (($target = $this->getTargetInternal($userId, $owner, $sourceNodeId)) !== null) {
			return $target;
		}

		$ownerUserFolder = $this->rootFolder->getUserFolder($owner->userId);
		$node = $ownerUserFolder->getFirstNodeById($sourceNodeId);
		if (!$node instanceof Node) {
			throw new NotFoundException();
		}

		$shareFolder = $this->config->getSystemValueString('share_folder', '/');
		if ($this->config->getSystemValueBool('sharing.allow_custom_share_folder', true)) {
			$shareFolder = $this->userConfig->getValueString($userId, Application::APP_ID, 'share_folder', $shareFolder);
		}

		$target = Filesystem::normalizePath($shareFolder . '/' . $node->getName());

		$qb = $this->dbConnection->getQueryBuilder();
		$qb
			->insert('sharing_source_node_target')
			->values([
				'user_id' => $qb->createNamedParameter($userId),
				'source_instance' => $qb->createNamedParameter($owner->instance),
				'source_node_id' => $qb->createNamedParameter($sourceNodeId),
				'target' => $qb->createNamedParameter($target),
			])
			->executeStatement();

		return $target;
	}

	public function getTarget(string $userId, ShareUser $owner, int $sourceNodeId): string {
		return $this->createDefaultTarget($userId, $owner, $sourceNodeId);
	}

	public function setTarget(string $userId, ShareUser $owner, int $sourceNodeId, string $target): void {
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
			->andWhere($qb->expr()->eq('source_node_id', $qb->createNamedParameter($sourceNodeId, IQueryBuilder::PARAM_INT)))
			->executeStatement();

		if ($rowCount === 0) {
			$qb = $this->dbConnection->getQueryBuilder();
			$qb
				->insert('sharing_source_node_target')
				->values([
					'user_id' => $qb->createNamedParameter($userId),
					'source_instance' => $qb->createNamedParameter($owner->instance),
					'source_node_id' => $qb->createNamedParameter($sourceNodeId),
					'target' => $qb->createNamedParameter($target),
				])
				->executeStatement();
		}
	}
}

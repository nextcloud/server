<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\App;

use OC\DB\SchemaChecker;
use OCP\App\IAppManager;
use OCP\Config\IUserConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Finds and deletes what an app leaves behind once it is disabled: its tables, app config, user preferences,
 * background jobs, migration records and app data folder. Uninstall steps are up to each app, and most leave
 * all of this in place, as does `occ app:remove`.
 *
 * An app's tables are found by replaying its migrations, which needs its code: purge an app before removing it.
 * Files it put in users' own folders are theirs, and are never touched.
 */
class AppDataPurger {
	public function __construct(
		private readonly IAppManager $appManager,
		private readonly IAppConfig $appConfig,
		private readonly IUserConfig $userConfig,
		private readonly IConfig $config,
		private readonly IDBConnection $connection,
		private readonly SchemaChecker $schemaChecker,
		private readonly IRootFolder $rootFolder,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * Why an app's data may not be purged, or null when it may.
	 */
	public function getRefusal(string $appId): ?string {
		if ($appId === '' || $this->appManager->cleanAppId($appId) !== $appId) {
			return "'$appId' is not a valid app id.";
		}
		if ($appId === 'core' || $this->appManager->isShipped($appId)) {
			return "App '$appId' is shipped with Nextcloud; its data cannot be purged.";
		}
		if ($this->appManager->isEnabledForAnyone($appId)) {
			return "App '$appId' is enabled. Disable it first with: occ app:disable $appId";
		}
		return null;
	}

	/**
	 * Apps that are not enabled but still have data, sorted by id.
	 *
	 * @return list<AppLeftovers>
	 */
	public function findAppsWithLeftovers(): array {
		// Apps that were once installed: those with a recorded version, and those whose migrations ran
		$candidates = array_keys($this->appConfig->getAppInstalledVersions());

		$qb = $this->connection->getQueryBuilder();
		$result = $qb->selectDistinct('app')->from('migrations')->executeQuery();
		while (($app = $result->fetchOne()) !== false) {
			$candidates[] = (string)$app;
		}
		$result->closeCursor();

		$candidates = array_unique($candidates);
		sort($candidates);

		$found = [];
		foreach ($candidates as $appId) {
			if ($this->getRefusal($appId) !== null) {
				continue;
			}
			try {
				$leftovers = $this->getLeftovers($appId);
			} catch (\RuntimeException) {
				// Listed anyway, so it is not overlooked; the details say why its tables are unknown
				$found[] = $this->getLeftoversWithoutTables($appId);
				continue;
			}
			if (!$leftovers->isEmpty()) {
				$found[] = $leftovers;
			}
		}
		return $found;
	}

	/**
	 * @throws \RuntimeException when the app's migrations cannot be read, so its tables are unknown
	 */
	public function getLeftovers(string $appId): AppLeftovers {
		try {
			$tables = $this->schemaChecker->getTablesCreatedBy($appId);
		} catch (\Throwable $e) {
			$this->logger->warning('Could not replay the migrations of app {app} to find its tables', ['app' => $appId, 'exception' => $e]);
			throw new \RuntimeException("Could not find the tables of app '$appId' from its migrations: " . $e->getMessage(), 0, $e);
		}
		$existing = [];
		foreach ($tables ?? [] as $table) {
			if ($this->connection->tableExists($this->withoutPrefix($table))) {
				$existing[] = $table;
			}
		}

		$appData = $this->getAppDataFolder($appId);

		return new AppLeftovers(
			appId: $appId,
			codePresent: $tables !== null,
			tables: $existing,
			configKeys: count($this->appConfig->getKeys($appId)),
			userPreferences: $this->count('preferences', 'appid', $appId),
			backgroundJobs: $this->countJobs($appId),
			migrations: $this->count('migrations', 'app', $appId),
			appDataSize: $appData === null ? null : $this->folderSize($appData),
		);
	}

	private function getLeftoversWithoutTables(string $appId): AppLeftovers {
		$appData = $this->getAppDataFolder($appId);
		return new AppLeftovers(
			appId: $appId,
			codePresent: true,
			tables: [],
			configKeys: count($this->appConfig->getKeys($appId)),
			userPreferences: $this->count('preferences', 'appid', $appId),
			backgroundJobs: $this->countJobs($appId),
			migrations: $this->count('migrations', 'app', $appId),
			appDataSize: $appData === null ? null : $this->folderSize($appData),
		);
	}

	/**
	 * Deletes all of what getLeftovers() found. The app's code, if still there, is left for `occ app:remove`.
	 */
	public function purge(AppLeftovers $leftovers): void {
		$appId = $leftovers->appId;
		$refusal = $this->getRefusal($appId);
		if ($refusal !== null) {
			throw new \InvalidArgumentException($refusal);
		}

		foreach ($leftovers->tables as $table) {
			$this->connection->dropTable($this->withoutPrefix($table));
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('migrations')
			->where($qb->expr()->eq('app', $qb->createNamedParameter($appId)))
			->executeStatement();

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('jobs')
			->where($qb->expr()->like('class', $qb->createNamedParameter($this->jobClassPattern($appId))))
			->executeStatement();

		$this->userConfig->deleteApp($appId);
		$this->appConfig->deleteApp($appId);
		$this->getAppDataFolder($appId)?->delete();

		$this->logger->info('Purged the data of app {app}', ['app' => $appId, 'leftovers' => $leftovers->toArray()]);
	}

	private function withoutPrefix(string $table): string {
		$prefix = $this->config->getSystemValueString('dbtableprefix', 'oc_');
		return str_starts_with($table, $prefix) ? substr($table, strlen($prefix)) : $table;
	}

	private function count(string $table, string $column, string $appId): int {
		$qb = $this->connection->getQueryBuilder();
		$qb->select($qb->func()->count('*'))
			->from($table)
			->where($qb->expr()->eq($column, $qb->createNamedParameter($appId)));
		return (int)$qb->executeQuery()->fetchOne();
	}

	private function countJobs(string $appId): int {
		$qb = $this->connection->getQueryBuilder();
		$qb->select($qb->func()->count('*'))
			->from('jobs')
			->where($qb->expr()->like('class', $qb->createNamedParameter($this->jobClassPattern($appId), IQueryBuilder::PARAM_STR)));
		return (int)$qb->executeQuery()->fetchOne();
	}

	/**
	 * Background jobs are classes in the app's namespace. Without the app's code its namespace is a guess,
	 * but Nextcloud drops jobs whose class no longer exists the next time it tries to run them anyway.
	 */
	private function jobClassPattern(string $appId): string {
		$namespace = $this->appManager->getAppNamespace($appId);
		return $this->connection->escapeLikeParameter($namespace . '\\') . '%';
	}

	/**
	 * Summed from the files themselves: a folder's own recorded size can lag behind what is in it.
	 */
	private function folderSize(Folder $folder): int {
		$size = 0;
		foreach ($folder->getDirectoryListing() as $node) {
			$size += $node instanceof Folder ? $this->folderSize($node) : (int)$node->getSize();
		}
		return $size;
	}

	private function getAppDataFolder(string $appId): ?Folder {
		$instanceId = $this->config->getSystemValueString('instanceid');
		if ($instanceId === '') {
			return null;
		}
		try {
			$folder = $this->rootFolder->get('appdata_' . $instanceId . '/' . $appId);
		} catch (NotFoundException) {
			return null;
		}
		return $folder instanceof Folder ? $folder : null;
	}
}

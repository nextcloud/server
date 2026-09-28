<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Cache;

use OC\SystemConfig;
use OC\User\DisplayNameCache;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IMimeTypeLoader;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class CacheDependencies {
	public function __construct(
		private IMimeTypeLoader $mimeTypeLoader,
		private IDBConnection $connection,
		private IEventDispatcher $eventDispatcher,
		private QuerySearchHelper $querySearchHelper,
		private SystemConfig $systemConfig,
		private LoggerInterface $logger,
		private IFilesMetadataManager $metadataManager,
		private DisplayNameCache $displayNameCache,
	) {
	}

	public function getMimeTypeLoader(): IMimeTypeLoader {
		return $this->mimeTypeLoader;
	}

	public function getConnection(): IDBConnection {
		return $this->connection;
	}

	public function getEventDispatcher(): IEventDispatcher {
		return $this->eventDispatcher;
	}

	public function getQuerySearchHelper(): QuerySearchHelper {
		return $this->querySearchHelper;
	}

	public function getSystemConfig(): SystemConfig {
		return $this->systemConfig;
	}

	public function getLogger(): LoggerInterface {
		return $this->logger;
	}

	public function getDisplayNameCache(): DisplayNameCache {
		return $this->displayNameCache;
	}

	public function getMetadataManager(): IFilesMetadataManager {
		return $this->metadataManager;
	}
}

<?php

namespace OC\Files\Cache;

use OC\SystemConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IMimeTypeLoader;
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
}

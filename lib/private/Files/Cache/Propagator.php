<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache;

use OC\DB\Exceptions\DbalException;
use OC\Files\Storage\LocalRootStorage;
use OC\Files\Storage\Wrapper\Encryption;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\IPropagator;
use OCP\Files\Storage\IReliableEtagStorage;
use OCP\Files\Storage\IStorage;
use OCP\IDBConnection;
use OCP\Server;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

class Propagator implements IPropagator {
	private ClockInterface $clock;
	private BatchPropagator $batchPropagator;

	public function __construct(
		protected readonly IStorage $storage,
		private readonly array $ignore = [],
	) {
		$this->clock = Server::get(ClockInterface::class);
		$this->batchPropagator = Server::get(BatchPropagator::class);
	}

	#[Override]
	public function propagateChange(string $internalPath, int $time, int $sizeDifference = 0): void {
		// Do not propagate changes in ignored paths
		foreach ($this->ignore as $ignore) {
			if (str_starts_with($internalPath, $ignore)) {
				return;
			}
		}

		$time = min($time, $this->clock->now()->getTimestamp());

		$storageId = $this->storage->getCache()->getNumericStorageId();

		$parents = $this->getParents($internalPath);
		if ($this->storage->instanceOfStorage(LocalRootStorage::class)) {
			if (str_starts_with($internalPath, '__groupfolders/versions') || str_starts_with($internalPath, '__groupfolders/trash')) {
				// Remove '', '__groupfolders' and '__groupfolders/versions' or '__groupfolders/trash'
				$parents = array_slice($parents, 3);
			} elseif (str_starts_with($internalPath, '__groupfolders')) {
				// Remove '' and '__groupfolders'
				$parents = array_slice($parents, 2);
			}
		}

		if ($parents === []) {
			return;
		}

		foreach ($parents as $parent) {
			$this->batchPropagator->addParent($storageId, $parent, $time, $sizeDifference);
		}
	}

	/**
	 * @return string[]
	 */
	protected function getParents(string $path): array {
		$parts = explode('/', $path);
		$parent = '';
		$parents = [];
		foreach ($parts as $part) {
			$parents[] = $parent;
			$parent = trim($parent . '/' . $part, '/');
		}
		return $parents;
	}

	#[Override]
	public function beginBatch(): void {}

	#[Override]
	public function commitBatch(): void {
		$this->batchPropagator->commit();
	}
}

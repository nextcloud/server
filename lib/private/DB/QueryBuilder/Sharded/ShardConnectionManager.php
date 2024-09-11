<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder\Sharded;

use OC\DB\ConnectionAdapter;
use OC\DB\ConnectionFactory;
use OC\SystemConfig;
use OCP\IDBConnection;

/**
 * Keeps track of the db connections to the various shards
 */
class ShardConnectionManager {
	/** @var array<string, IDBConnection> */
	private array $connections = [];

	public function __construct(
		private SystemConfig $config,
		private ConnectionFactory $factory,
	) {
	}

	public function getConnection(ShardDefinition $shardDefinition, int $shard): IDBConnection {
		$connectionKey = $shardDefinition->table . '_' . $shard;
		if (!isset($this->connections[$connectionKey])) {
			$this->connections[$connectionKey] = $this->createConnection($shardDefinition->shards[$shard]);
		}

		return $this->connections[$connectionKey];
	}

	private function createConnection(array $shardConfig): IDBConnection {
		$shardConfig['sharding'] = [];
		$type = $this->config->getValue('dbtype', 'sqlite');
		return new ConnectionAdapter($this->factory->getConnection($type, $shardConfig));
	}
}

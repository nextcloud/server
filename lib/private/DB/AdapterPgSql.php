<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

class AdapterPgSql extends Adapter {

	public function lastInsertId($table) {
		$result = $this->conn->executeQuery('SELECT lastval()');
		$val = $result->fetchOne();
		$result->free();
		return (int)$val;
	}

	public const UNIX_TIMESTAMP_REPLACEMENT = 'cast(extract(epoch from current_timestamp) as integer)';
	public function fixupStatement($statement) {
		$statement = str_replace('`', '"', $statement);
		$statement = str_ireplace('UNIX_TIMESTAMP()', self::UNIX_TIMESTAMP_REPLACEMENT, $statement);
		return $statement;
	}

	public function insertIgnoreConflict(string $table, array $values) : int {
		// "upsert" is only available since PgSQL 9.5, but the generic way
		// would leave error logs in the DB.
		$builder = $this->conn->getQueryBuilder();
		$builder->insert($table);
		foreach ($values as $key => $value) {
			$builder->setValue($key, $builder->createNamedParameter($value));
		}
		$queryString = $builder->getSQL() . ' ON CONFLICT DO NOTHING';
		return $this->conn->executeUpdate($queryString, $builder->getParameters(), $builder->getParameterTypes());
	}
}

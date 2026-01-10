<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use Doctrine\DBAL\Schema\AbstractAsset;
use OCP\IConfig;
use Throwable;

/**
 * PostgreSQL specific helper functions.
 */
class PgSqlTools {
	public function __construct(
		private IConfig $config
	) {
	}

	/**
	 * Resynchronizes all sequences of a database after using INSERTs without leaving out the auto-incremented column.
	 *
	 * When rows are inserted without using nextval (e.g. after bulk import, restore, manual insert), the sequence counter
	 * can lag behind existing values. Resynchronizing avoids primary key collisions.
	 *
	 * Walks every PostgreSQL sequence and makes sure the sequence's current value is at least the maximum value present
	 * in the column that uses that sequence. This prevents later INSERTs from producing duplicate primary keys after
	 * the noted operations.
	 *
	 * @throws \RuntimeException when a sequence has multiple/no owners (unsupported) or when privileges are insufficient
	 */
	public function resynchronizeDatabaseSequences(Connection $conn): void {
		$conn->getConfiguration()->setSchemaAssetsFilter(fn($asset) => $this->schemaAssetMatches($asset));
		$platform = $conn->getDatabasePlatform();

		// Feature-detect whether the pg_catalog.pg_sequences view is available/accessible.
		// If unavailable, we'll fall back to the quoted-identifier method for reading last_value.
		$usePgSequences = $this->canUsePgSequences($conn);

		/* Enumerate sequences via Doctrine DBAL then for each sequence try to find the table/column that uses it. Specifically:
		 * - try to find the table/column that uses it via pg_depend (authoritative)
		 * - if no matches are found then fallback to information_schema textual match
		 * - If still no matches are found, throw
		 * - If multiple matches are found, throw (unsupported)
		 * - If a single owner is found (normal situation), properly quote table, column, and sequence identifiers
		 * - Check for sufficient privileges to modify (UPDATE) the sequence
		 * - If we lack privileges, throw
		 * - Set sequence generator value as deemed appropriate (server-side single statement)
		 */
		foreach ($conn->createSchemaManager()->listSequences() as $sequence) {
			$sequenceName = $sequence->getName();

			// 1) Lookup sequence owner (may return zero/multiple rows)
			$ownerRows = $this->findSequenceOwner($conn, $sequenceName);
			
			// 2) No owner found; unsupported -- throw
			if (count($ownerRows) === 0) {
				// fail so this can be investigated
				throw new \RuntimeException(sprintf(
					"Sequence '%s' has no owning column (pg_depend and information_schema returned no match). To investigate, run:\n" .
					"  SELECT table_schema, table_name, column_name\n" .
					"  FROM information_schema.columns\n" .
					"  WHERE column_default LIKE '%%nextval(%s::regclass)%%';\n" .
					"Replace %s with the sequence name (e.g. public.my_seq) and run in psql.",
					$sequenceName, $sequenceName, $sequenceName
				));
			}

			// 3) Multiple owners; unsupported -- throw
			if (count($ownerRows) > 1) {
				// Build readable owner list for the error message
				$owners = [];
				foreach ($ownerRows as $row) {
					$owners[] = ($row['table_schema'] ?? '') . '.' . ($row['table_name'] ?? '') . '.' . ($row['column_name'] ?? '');
				}
				throw new \RuntimeException('Sequence "' . $sequenceName . '" is referenced by multiple columns: ' . implode(', ', $owners));
			}

			// 4) Single owner -> prepare quoted schema/table/column identifiers
			$ownerInfo = $ownerRows[0];
			if (!isset($ownerInfo['table_schema'], $ownerInfo['table_name'], $ownerInfo['column_name'])) {
				throw new \RuntimeException(sprintf(
					'Unexpected owner info for sequence "%s": %s', $sequenceName, json_encode($ownerInfo)
				));
			}
			// table/column always come from either pg_depend (preferred) or the information_schema fallback
			$tableSchema = $ownerInfo['table_schema'];
			$tableName = $ownerInfo['table_name'];
			$columnName = $ownerInfo['column_name'];

			$quotedTable = $platform->quoteIdentifier($tableSchema) . '.' . $platform->quoteIdentifier($tableName);
			$quotedColumn = $platform->quoteIdentifier($columnName);

			// 5) Determine a quoted sequence identifier for reading last_value if we fall back to the quoted-identifier approach.
			// Prefer catalog values (seq_schema/seq_name returned by pg_depend) if present
			if (isset($ownerInfo['seq_schema'], $ownerInfo['seq_name'])) {
				$quotedSequence = $platform->quoteIdentifier($ownerInfo['seq_schema']) . '.' . $platform->quoteIdentifier($ownerInfo['seq_name']);
			} elseif (strpos($sequenceName, '.') !== false) {
				[$seqSchema, $seqOnly] = explode('.', $sequenceName, 2);
				$seqSchema = trim($seqSchema, '"');
				$seqOnly = trim($seqOnly, '"');
				$quotedSequence = $platform->quoteIdentifier($seqSchema) . '.' . $platform->quoteIdentifier($seqOnly);
			} else {
				// unqualified sequence name: let search_path/regclass resolution apply (quote the identifier only)
				$quotedSequence = $platform->quoteIdentifier($sequenceName);
			}

			// 6) Privilege check: setval requires UPDATE (or ownership)
			$hasUpdate = $this->hasUpdatePrivilege($conn, $sequenceName);
			if (!$hasUpdate) {
				throw new \RuntimeException(sprintf(
					'Cannot resynchronize sequence "%s": missing UPDATE privilege. Inspect privileges or run as a role that owns the sequence (or has UPDATE).',
					$sequenceName
				));
			}

			// 7) Build setval SQL and params (pg_sequences path or fallback), then execute.
			[$setvalSql, $bindParams] = $this->buildSetvalSqlAndParams(
				$usePgSequences,
				$quotedColumn,
				$quotedTable,
				$quotedSequence,
				$sequenceName
			);
			// execute (bind parameters determined above)
			$conn->executeStatement($setvalSql, $bindParams);
		}
	}

	/**
	 * Detect whether pg_catalog.pg_sequences is available and readable.
	 *
	 * pg_sequences allows reading last_value via server-side lookup using the same regclass resolution
	 * and avoids any quoted-identifier / search_path mismatches.
	 *
	 * @return bool True when pg_catalog.pg_sequences exists and is accessible.
	 */
	private function canUsePgSequences(Connection $conn): bool {
		try {
			return ((int) $conn
				->executeQuery("SELECT (to_regclass('pg_catalog.pg_sequences') IS NOT NULL)::int")
				->fetchOne()) === 1;
		} catch (Throwable $e) {
			return false;
		}
	}

	/**
	 * Find the owning column(s) for a sequence.
	 *
	 * Discovered by pg_catalog.pg_depend (preferred) or information_schema (fallback).
	 * May return zero/multiple rows.
	 *
	 * Returns an array of associative rows. Each row contains:
	 * - table_schema, table_name, column_name
	 * Optionally (when discovered via pg_depend) also:
	 * - seq_schema, seq_name
	 *
	 * @return array<int, array{
	 *     table_schema: string,
	 *     table_name: string,
	 *     column_name: string,
	 *     seq_schema?: string,
	 *     seq_name?: string
	 * }>
	 */
	private function findSequenceOwner(Connection $conn, string $sequenceName): array {
		// Try authoritative owner lookup via pg_depend
		$sqlDepend = <<<'SQL'
-- Find the sequence catalog row and the table/column that depends on it.
-- Why: use pg_depend (authoritative catalog) to discover the actual owning column for a sequence.
-- Returned columns: seq_schema, seq_name, table_schema, table_name, column_name.
-- Caveats: ?::regclass resolves names via search_path; dep.refobjsubid <> 0 restricts to column-level dependencies.
SELECT
	seq_ns.nspname	AS seq_schema,		-- sequence schema (pg_namespace)
	seq.relname		AS seq_name,		-- sequence name (pg_class)
	tbl_ns.nspname	AS table_schema,	-- dependent table schema
	tbl.relname		AS table_name,		-- dependent table name (pg_class)
	col.attname		AS column_name		-- dependent column name (pg_attribute)
FROM pg_catalog.pg_class AS seq
JOIN pg_catalog.pg_namespace AS seq_ns
	ON seq_ns.oid = seq.relnamespace					-- resolve sequence -> namespace by OID
JOIN pg_catalog.pg_depend AS dep
	ON dep.objid = seq.oid
	AND dep.classid = 'pg_catalog.pg_class'::regclass	-- dependency entries whose object is the system sequence table
JOIN pg_catalog.pg_class AS tbl
	ON dep.refobjid = tbl.oid							-- object that depends on the sequence (usually a table)
JOIN pg_catalog.pg_namespace AS tbl_ns
	ON tbl_ns.oid = tbl.relnamespace					-- resolve table -> namespace
JOIN pg_catalog.pg_attribute AS col
	ON col.attrelid = tbl.oid
	AND col.attnum = dep.refobjsubid					-- specific column (attribute number) that depends on the sequence
WHERE
	seq.relkind = 'S'			-- restrict to sequences
	AND seq.oid = ?::regclass	-- bind sequence name and resolve to OID (respects search_path)
	AND dep.refobjsubid <> 0	-- only column-level dependencies (exclude whole-relation deps)

SQL;
		$ownerRows = $conn->executeQuery($sqlDepend, [$sequenceName])->fetchAllAssociative();
		if (count($ownerRows) > 0) {
			return $ownerRows;
		}

		// Fallback to information_schema textual match if pg_depend found nothing
		$sqlInfo = <<<'SQL'
-- Fallback: heuristic text-match on information_schema for NEXTVAL default.
SELECT
	table_schema,
	table_name,
	column_name
FROM information_schema.columns
WHERE
	column_default = ('nextval(' || quote_literal(?) || '::regclass)')
	AND table_catalog = current_database()
SQL;
		return $conn->executeQuery($sqlInfo, [$sequenceName])->fetchAllAssociative();
	}

	/**
	 * Check whether current role has UPDATE privilege on the given sequence (either explicitly or by virtue of being the owner).
	 *
	 * @return bool True if UPDATE privilege (or ownership) is present, false otherwise.
	 */
	private function hasUpdatePrivilege(Connection $conn, string $sequenceName): bool {
		$privilegeInt = $conn
			->executeQuery('SELECT (has_sequence_privilege(?::regclass, \'UPDATE\'))::int', [$sequenceName])
			->fetchOne();
		return ((int) $privilegeInt) === 1;
	}

	/**
	 * Build the setval SQL and bind parameters for the chosen strategy.
	 *
	 * Either uses pg_sequences (preferred) path or the quoted-identifier fallback. Both methods use a single server-side 
	 * setval using GREATEST+COALESCE+MAX+last_value to avoid moving sequence backwards.
	 *
	 * Prefer pg_catalog.pg_sequences when available so both reads use regclass-based resolution and we avoid embedding/quoting
	 * a sequence identifier in the subselect. 
	 *
	 * If pg_sequences is unavailable (permissions, or nonstandard environments), fall back to reading last_value from the quoted
	 * identifier.
	 *
	 * When $usePgSequences is true the returned SQL uses a WITH target AS (SELECT ?::regclass::text AS reg)
 	 * and the returned params are: [ <target_reg_text>, <setval_regclass> ].
	 * Otherwise the returned params are: [ <setval_regclass> ].
	 *
	 * @return array{0:string,1:array<int,string>} [$sql, $params] 
	 */
	private function buildSetvalSqlAndParams(bool $usePgSequences, string $quotedColumn, string $quotedTable, string $quotedSequence, string $sequenceName): array {
		if ($usePgSequences) {
			$setvalSql = <<<'SQL'
-- Set the sequence value safely using pg_catalog.pg_sequences for last_value lookup.
-- Behavior:
--  - Uses GREATEST(...) so the sequence is set to the larger of the current MAX(column) and the sequence's last_value.
--  - COALESCE(MAX(...), 0) makes an empty table behave as 0.
--  - We read last_value from pg_catalog.pg_sequences using regclass::text to derive schema/name so resolution matches ?::regclass.
--  - The final 'true' marks the sequence as "is_called" so next nextval() yields last_value+1.
WITH target AS (SELECT ?::regclass::text AS reg)
SELECT setval(
	?::regclass,  -- bind the sequence name (resolves via search_path or schema qualification)
	GREATEST(
		COALESCE((SELECT MAX({column}) FROM {table}), 0),
		(
			SELECT last_value FROM pg_catalog.pg_sequences ps, target t
			WHERE ps.schemaname = CASE
				WHEN strpos(t.reg, '.') > 0
					THEN split_part(t.reg, '.', 1)
					ELSE current_schema()
				END
			AND ps.sequencename = CASE
				WHEN strpos(t.reg, '.') > 0
					THEN split_part(t.reg, '.', 2)
					ELSE split_part(t.reg, '.', 1)
				END
		)
	),
	true
)
SQL;
			$setvalSql = strtr($setvalSql, [
				'{column}' => $quotedColumn,
				'{table}'  => $quotedTable,
			]);
			// Bind order (using pg_sequences) -> [target_reg_text, setval_regclass]
			return [$setvalSql, [$sequenceName, $sequenceName]];
		}

		// fallback: read last_value from the quoted sequence identifier
		$setvalSql = <<<'SQL'
-- Set the sequence value safely, never moving it backwards.
-- Behavior:
--  - Uses GREATEST(...) so the sequence is set to the larger of the current MAX(column) and the sequence's last_value.
--  - COALESCE(MAX(...), 0) makes an empty table behave as 0.
--  - Reading last_value from the quoted {sequence} keeps the legacy behavior as a fallback.
--  - The final 'true' marks the sequence as "is_called" so next nextval() yields last_value+1.
SELECT setval(
	?::regclass,
	GREATEST(
		COALESCE((SELECT MAX({column}) FROM {table}), 0),
		(SELECT last_value FROM {sequence})
	),
	true
)
SQL;
		$setvalSql = strtr($setvalSql, [
			'{column}'   => $quotedColumn,
			'{table}'    => $quotedTable,
			'{sequence}' => $quotedSequence,
		]);
		// Bind order (using quoted identifier fallback) -> [setval_regclass]
		return [$setvalSql, [$sequenceName]];
	}

	/**
	 * Schema assets filter used by Doctrine SchemaManager.
	 *
	 * TODO: Should perhaps be a shared utility function (since we filter elsewhere too)
	 *
	 * @return bool True if the asset name starts with the configured table prefix.
	 */
	private function schemaAssetMatches(string|AbstractAsset $asset): bool {
		$tablePrefix = $this->config->getSystemValueString('dbtableprefix', 'oc_');
		$assetName = $asset instanceof AbstractAsset ? $asset->getName() : (string)$asset;
		return str_starts_with($assetName, $tablePrefix);
	}
}

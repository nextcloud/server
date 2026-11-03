-- SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
-- SPDX-License-Identifier: AGPL-3.0-or-later
--
-- Nextcloud ORA-22858 upgrade failure: manual conversion of the jobs
-- "argument" column from VARCHAR2(4000) to CLOB.
--
-- Context: `occ upgrade` to Nextcloud >= 32.0.7 / 33.0.1 / 34.x fails on
-- Oracle with "ORA-22858: invalid alteration of datatype" in core migration
-- 34000Date20260318095645, because Oracle cannot ALTER a VARCHAR2 column to
-- CLOB in place. This script performs the conversion the way Oracle
-- requires (add CLOB column, copy, verify, drop, rename). Afterwards,
-- re-run `occ upgrade`: the migration detects the already-converted column
-- and completes without further changes.
--
-- Run as the Nextcloud database user, with Nextcloud in maintenance mode.
-- IMPORTANT: also verify that cron jobs and web/app workers are actually
-- stopped, not merely that maintenance mode is set.
--     sqlplus <nc_user>@<service> @nc-ora22858-workaround.sql
-- (SQLcl works identically. If you use a different client: replace the two
-- substitution variables - the table name and the backup confirmation - in
-- the DECLARE...END; block below with literal values and execute that block
-- on its own. The prompts, SPOOL and the state listings are the only
-- SQL*Plus-specific parts.)
--
-- Safety properties:
--   * Aborts before any change unless a restorable backup is explicitly
--     confirmed (empty input aborts).
--   * Inspects the current column state first and handles a previously
--     interrupted run: it is safe to run this script multiple times.
--   * Refuses to proceed if any customer-added index or named constraint
--     references the column (DROP COLUMN would silently remove them).
--   * The data copy is verified (DBMS_LOB.COMPARE) BEFORE it is committed;
--     on any mismatch everything is rolled back and the temporary column is
--     removed - the schema is left exactly as it was found.
--   * The copy column is locked down (NOT NULL) before the original column
--     is dropped, so a forgotten writer fails loudly instead of losing data.
--   * A full transcript is written to nc_ora22858_workaround.log - attach
--     it to the support ticket.
--
-- Runtime scales with table size (the verification compares every row);
-- on a normally-sized jobs table this completes in seconds.

SET SERVEROUTPUT ON SIZE UNLIMITED
SET VERIFY OFF
SET FEEDBACK ON
WHENEVER SQLERROR EXIT FAILURE ROLLBACK

SPOOL nc_ora22858_workaround.log

ACCEPT table_name CHAR DEFAULT 'oc_jobs' PROMPT 'Jobs table name (check dbtableprefix in config.php) [oc_jobs]: '
ACCEPT backup_confirmed CHAR DEFAULT 'no' PROMPT 'Is a restorable database backup confirmed? (yes/no) [no]: '

PROMPT
PROMPT === Column state before ===
SELECT column_name, data_type, data_length, nullable
  FROM user_tab_columns
 WHERE table_name = '&table_name'
 ORDER BY column_id;

DECLARE
	l_table         CONSTANT VARCHAR2(128) := '&table_name';
	l_table_exists  INTEGER;
	l_old_type      user_tab_columns.data_type%TYPE;
	l_old_nullable  user_tab_columns.nullable%TYPE;
	l_new_type      user_tab_columns.data_type%TYPE;
	l_rows          INTEGER;
	l_nulls         INTEGER;
	l_count         INTEGER;
	l_names         VARCHAR2(4000);

	PROCEDURE fail(p_msg IN VARCHAR2) IS
	BEGIN
		RAISE_APPLICATION_ERROR(-20001, p_msg);
	END;

	PROCEDURE say(p_msg IN VARCHAR2) IS
	BEGIN
		DBMS_OUTPUT.PUT_LINE(p_msg);
	END;

	-- Restore NOT NULL on "argument" if it is currently nullable and holds
	-- no NULLs. Used by every path that can encounter a rebuilt column, so
	-- a run interrupted before the constraint was restored is repaired by
	-- the next run.
	PROCEDURE restore_not_null IS
		l_nullable  user_tab_columns.nullable%TYPE;
		l_null_rows INTEGER;
	BEGIN
		SELECT nullable INTO l_nullable
		  FROM user_tab_columns
		 WHERE table_name = l_table AND column_name = 'argument';
		IF l_nullable = 'Y' THEN
			EXECUTE IMMEDIATE 'SELECT COUNT(*) FROM "' || l_table
				|| '" WHERE "argument" IS NULL' INTO l_null_rows;
			IF l_null_rows = 0 THEN
				EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
					|| '" MODIFY ("argument" NOT NULL)';
				say('Restored NOT NULL constraint on "argument".');
			ELSE
				say('WARNING: ' || l_null_rows || ' NULL values present, NOT '
					|| 'NULL constraint not restored - report this in the ticket.');
			END IF;
		END IF;
	END;
BEGIN
	IF NVL(LOWER(TRIM('&backup_confirmed')), 'no') NOT IN ('yes', 'y') THEN
		fail('Aborted: restorable backup not confirmed. Nothing was changed.');
	END IF;

	-- Wait up to 60s for transient locks instead of failing immediately
	-- with ORA-00054 (DDL default is NOWAIT).
	EXECUTE IMMEDIATE 'ALTER SESSION SET DDL_LOCK_TIMEOUT = 60';

	SELECT COUNT(*) INTO l_table_exists
	  FROM user_tables WHERE table_name = l_table;
	IF l_table_exists = 0 THEN
		fail('Table "' || l_table || '" not found in this schema. Check the '
			|| 'dbtableprefix in config.php and that you are connected as the '
			|| 'Nextcloud database user. Nothing was changed.');
	END IF;

	BEGIN
		SELECT data_type, nullable INTO l_old_type, l_old_nullable
		  FROM user_tab_columns
		 WHERE table_name = l_table AND column_name = 'argument';
	EXCEPTION WHEN NO_DATA_FOUND THEN
		l_old_type := NULL;
	END;

	BEGIN
		SELECT data_type INTO l_new_type
		  FROM user_tab_columns
		 WHERE table_name = l_table AND column_name = 'argument2';
	EXCEPTION WHEN NO_DATA_FOUND THEN
		l_new_type := NULL;
	END;

	-- State: already converted (also the state after a successful run, or
	-- after a run interrupted before the NOT NULL constraint was restored).
	IF l_old_type = 'CLOB' AND l_new_type IS NULL THEN
		restore_not_null;
		say('Column "argument" is already CLOB - nothing else to do.');
		say('Re-run `occ upgrade` if it has not completed yet.');
		RETURN;
	END IF;

	-- State: unexpected - do not guess.
	IF l_old_type = 'CLOB' AND l_new_type IS NOT NULL THEN
		fail('Unexpected state: "argument" is already CLOB but "argument2" '
			|| 'also exists. Manual inspection required. Nothing was changed.');
	END IF;

	-- State: interrupted between DROP and RENAME (copy was verified and
	-- committed before the drop, so finishing the rename is safe).
	IF l_old_type IS NULL AND l_new_type = 'CLOB' THEN
		say('Resuming interrupted run: renaming "argument2" to "argument".');
		EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
			|| '" RENAME COLUMN "argument2" TO "argument"';
		EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
			|| '" MODIFY ("argument" DEFAULT '''')';
		restore_not_null;
		say('SUCCESS (resumed run completed). Re-run `occ upgrade` now.');
		RETURN;
	END IF;

	IF l_old_type IS NULL THEN
		fail('Column "argument" not found on "' || l_table || '" - '
			|| 'unexpected schema. Nothing was changed.');
	END IF;

	IF l_old_type <> 'VARCHAR2' THEN
		fail('Unexpected type for "argument": ' || l_old_type
			|| ' (expected VARCHAR2). Nothing was changed.');
	END IF;

	-- Pre-flight: DROP COLUMN silently removes indexes and constraints that
	-- reference the column. The stock schema has none on "argument" (the
	-- system NOT NULL constraint is recreated by this script); anything else
	-- is a customisation that must be reviewed by a human first.
	SELECT COUNT(*), LISTAGG(index_name, ', ') INTO l_count, l_names
	  FROM user_ind_columns
	 WHERE table_name = l_table AND column_name = 'argument';
	IF l_count > 0 THEN
		fail('Non-stock schema: index(es) reference "argument": ' || l_names
			|| '. Dropping the column would silently remove them. Manual '
			|| 'review required. Nothing was changed.');
	END IF;
	SELECT COUNT(*), LISTAGG(c.constraint_name, ', ') INTO l_count, l_names
	  FROM user_cons_columns cc
	  JOIN user_constraints c
	    ON c.constraint_name = cc.constraint_name AND c.table_name = cc.table_name
	 WHERE cc.table_name = l_table AND cc.column_name = 'argument'
	   AND c.generated <> 'GENERATED NAME';
	IF l_count > 0 THEN
		fail('Non-stock schema: named constraint(s) reference "argument": '
			|| l_names || '. Dropping the column would silently remove them. '
			|| 'Manual review required. Nothing was changed.');
	END IF;

	-- State: interrupted after ADD/copy but before DROP - the temporary
	-- CLOB column may hold a partial copy; remove it and redo from scratch.
	-- An "argument2" of any other type was not created by this script.
	IF l_new_type IS NOT NULL AND l_new_type <> 'CLOB' THEN
		fail('Unexpected state: column "argument2" exists with type '
			|| l_new_type || ' - not created by this script. Manual '
			|| 'inspection required. Nothing was changed.');
	ELSIF l_new_type IS NOT NULL THEN
		say('Previous interrupted run detected: dropping "argument2" and '
			|| 'redoing the copy.');
		EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
			|| '" DROP COLUMN "argument2"';
	END IF;

	-- Fresh conversion.
	EXECUTE IMMEDIATE 'SELECT COUNT(*) FROM "' || l_table || '"' INTO l_rows;
	EXECUTE IMMEDIATE 'SELECT COUNT(*) FROM "' || l_table
		|| '" WHERE "argument" IS NULL' INTO l_nulls;
	say('Rows: ' || l_rows || ', NULL arguments: ' || l_nulls);
	IF l_nulls > 0 THEN
		fail(l_nulls || ' NULL values in "argument" - stop and report this '
			|| 'in the ticket. Nothing was changed.');
	END IF;

	say('Adding temporary CLOB column and copying data...');
	EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table || '" ADD ("argument2" CLOB)';
	EXECUTE IMMEDIATE 'UPDATE "' || l_table || '" SET "argument2" = "argument"';
	say('Copied ' || SQL%ROWCOUNT || ' rows.');

	-- Verification gate - runs BEFORE the copy is committed.
	EXECUTE IMMEDIATE 'SELECT COUNT(*) FROM "' || l_table
		|| '" WHERE ("argument" IS NOT NULL AND "argument2" IS NULL)'
		|| ' OR ("argument" IS NULL AND "argument2" IS NOT NULL)'
		|| ' OR DBMS_LOB.COMPARE("argument2", TO_CLOB("argument")) <> 0'
		INTO l_count;
	IF l_count <> 0 THEN
		ROLLBACK;
		EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
			|| '" DROP COLUMN "argument2"';
		fail('Verification failed: ' || l_count || ' rows did not copy '
			|| 'identically. The copy was rolled back and the temporary '
			|| 'column removed - the original column is untouched. Report '
			|| 'this in the ticket.');
	END IF;
	COMMIT;
	say('Copy verified: 0 mismatches.');

	-- Lock the copy down before dropping the original: a straggling writer
	-- (cron/web worker that should have been stopped) now fails loudly on
	-- insert instead of silently losing its "argument" value in the drop.
	IF l_old_nullable = 'N' THEN
		EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
			|| '" MODIFY ("argument2" NOT NULL)';
	END IF;

	say('Swapping columns...');
	EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table || '" DROP COLUMN "argument"';
	EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
		|| '" RENAME COLUMN "argument2" TO "argument"';
	EXECUTE IMMEDIATE 'ALTER TABLE "' || l_table
		|| '" MODIFY ("argument" DEFAULT '''')';

	-- Final checks.
	EXECUTE IMMEDIATE 'SELECT COUNT(*) FROM "' || l_table || '"' INTO l_count;
	IF l_count <> l_rows THEN
		fail('Row count changed during conversion: ' || l_rows || ' -> '
			|| l_count || '. Restore from backup and report this.');
	END IF;
	SELECT data_type INTO l_old_type
	  FROM user_tab_columns
	 WHERE table_name = l_table AND column_name = 'argument';
	IF l_old_type <> 'CLOB' THEN
		fail('Post-check failed: "argument" is ' || l_old_type
			|| ', expected CLOB.');
	END IF;

	say('SUCCESS: "argument" converted to CLOB, ' || l_rows
		|| ' rows verified intact. Re-run `occ upgrade` now.');
END;
/

PROMPT
PROMPT === Column state after ===
SELECT column_name, data_type, data_length, nullable
  FROM user_tab_columns
 WHERE table_name = '&table_name'
 ORDER BY column_id;

SPOOL OFF
EXIT

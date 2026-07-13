-- SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
-- SPDX-License-Identifier: AGPL-3.0-or-later
--
-- READ-ONLY pre-flight diagnostics for the Nextcloud ORA-22858 workaround.
--
-- Run this BEFORE nc-ora22858-workaround.sql and send back the log file.
-- It performs no changes of any kind - only data-dictionary and count
-- queries - and is safe to run at any time, on any state of the table.
-- Sections that need privileges your database user does not have are
-- reported as "not visible to this user" instead of failing.
--
-- Run as the Nextcloud database user:
--     sqlplus <nc_user>@<service> @nc-ora22858-preflight.sql
--
-- The transcript is written to nc_ora22858_preflight.log in the directory
-- you run sqlplus from.

SET SERVEROUTPUT ON SIZE UNLIMITED
SET VERIFY OFF
SET FEEDBACK OFF
WHENEVER SQLERROR CONTINUE

SPOOL nc_ora22858_preflight.log

ACCEPT table_name CHAR DEFAULT 'oc_jobs' PROMPT 'Jobs table name (check dbtableprefix in config.php) [oc_jobs]: '

DECLARE
	l_table    CONSTANT VARCHAR2(128) := '&table_name';
	l_count    INTEGER;
	l_count2   INTEGER;
	l_text     VARCHAR2(4000);

	PROCEDURE say(p_msg IN VARCHAR2) IS
	BEGIN
		DBMS_OUTPUT.PUT_LINE(p_msg);
	END;

	PROCEDURE section(p_title IN VARCHAR2) IS
	BEGIN
		say('');
		say('=== ' || p_title || ' ===');
	END;
BEGIN
	say('NC-ORA22858 PREFLIGHT v1 - read-only, no changes are made');

	section('Context');
	say('User: ' || USER);
	SELECT TO_CHAR(SYSDATE, 'YYYY-MM-DD HH24:MI:SS') INTO l_text FROM dual;
	say('Date: ' || l_text);
	say('Table under inspection: "' || l_table || '"');

	section('Oracle server version');
	BEGIN
		SELECT version_full INTO l_text
		  FROM product_component_version WHERE ROWNUM = 1;
		say(l_text);
	EXCEPTION WHEN OTHERS THEN
		BEGIN
			SELECT version INTO l_text
			  FROM product_component_version WHERE ROWNUM = 1;
			say(l_text);
		EXCEPTION WHEN OTHERS THEN
			say('not visible to this user - please supply the output of: '
				|| 'SELECT banner FROM v$version;');
		END;
	END;

	section('Table and columns');
	SELECT COUNT(*) INTO l_count FROM user_tables WHERE table_name = l_table;
	IF l_count = 0 THEN
		say('TABLE NOT FOUND in this schema - check dbtableprefix and the '
			|| 'connected user. Remaining sections will be empty.');
	ELSE
		say('Table found.');
		FOR r IN (SELECT column_name, data_type, data_length, char_used, nullable
		            FROM user_tab_columns
		           WHERE table_name = l_table ORDER BY column_id) LOOP
			say('  "' || r.column_name || '" ' || r.data_type
				|| '(' || r.data_length
				|| CASE r.char_used WHEN 'C' THEN ' CHAR' WHEN 'B' THEN ' BYTE' ELSE '' END
				|| ') nullable=' || r.nullable);
		END LOOP;
	END IF;

	section('Row statistics');
	BEGIN
		EXECUTE IMMEDIATE 'SELECT COUNT(*), COUNT(*) - COUNT("argument") FROM "'
			|| l_table || '"' INTO l_count, l_count2;
		say('Rows: ' || l_count || ', NULL arguments: ' || l_count2);
		EXECUTE IMMEDIATE 'SELECT NVL(MAX(LENGTH("argument")), 0) FROM "'
			|| l_table || '"' INTO l_count;
		say('Longest argument value: ' || l_count || ' chars');
	EXCEPTION WHEN OTHERS THEN
		say('could not read rows: ' || SQLERRM);
	END;

	section('Indexes on the table (any on "argument" blocks the workaround)');
	l_count := 0;
	FOR r IN (SELECT index_name, column_name FROM user_ind_columns
	           WHERE table_name = l_table ORDER BY index_name, column_position) LOOP
		l_count := l_count + 1;
		say('  ' || r.index_name || ' (' || r.column_name || ')'
			|| CASE WHEN r.column_name = 'argument' THEN '  <-- ON THE TARGET COLUMN' ELSE '' END);
	END LOOP;
	IF l_count = 0 THEN say('  none'); END IF;

	section('Constraints referencing "argument" (named ones block the workaround)');
	l_count := 0;
	FOR r IN (SELECT c.constraint_name, c.constraint_type, c.generated
	            FROM user_cons_columns cc
	            JOIN user_constraints c
	              ON c.constraint_name = cc.constraint_name AND c.table_name = cc.table_name
	           WHERE cc.table_name = l_table AND cc.column_name = 'argument') LOOP
		l_count := l_count + 1;
		say('  ' || r.constraint_name || ' type=' || r.constraint_type
			|| ' generated=' || r.generated);
	END LOOP;
	IF l_count = 0 THEN say('  none'); END IF;

	section('Triggers on the table (would need review before the swap)');
	l_count := 0;
	FOR r IN (SELECT trigger_name, status FROM user_triggers
	           WHERE table_name = l_table) LOOP
		l_count := l_count + 1;
		say('  ' || r.trigger_name || ' (' || r.status || ')');
	END LOOP;
	IF l_count = 0 THEN say('  none'); END IF;

	section('Materialized view logs on the table');
	BEGIN
		SELECT COUNT(*) INTO l_count FROM user_mview_logs
		 WHERE master = l_table;
		say(CASE WHEN l_count = 0 THEN '  none' ELSE '  ' || l_count || ' FOUND - tell us' END);
	EXCEPTION WHEN OTHERS THEN
		say('  not visible to this user');
	END;

	section('Flashback archive enrollment');
	BEGIN
		EXECUTE IMMEDIATE
			'SELECT COUNT(*) FROM user_flashback_archive_tables WHERE table_name = :t'
			INTO l_count USING l_table;
		say(CASE WHEN l_count = 0 THEN '  not enrolled' ELSE '  ENROLLED - tell us before running' END);
	EXCEPTION WHEN OTHERS THEN
		say('  not visible to this user');
	END;

	section('VPD / security policies on the table');
	BEGIN
		EXECUTE IMMEDIATE
			'SELECT COUNT(*) FROM user_policies WHERE object_name = :t'
			INTO l_count USING l_table;
		say(CASE WHEN l_count = 0 THEN '  none' ELSE '  ' || l_count || ' FOUND - tell us' END);
	EXCEPTION WHEN OTHERS THEN
		say('  not visible to this user');
	END;

	section('Objects depending on the table (views etc. - briefly invalid during the swap)');
	l_count := 0;
	FOR r IN (SELECT name, type FROM user_dependencies
	           WHERE referenced_name = l_table AND referenced_type = 'TABLE') LOOP
		l_count := l_count + 1;
		say('  ' || r.type || ' ' || r.name);
	END LOOP;
	IF l_count = 0 THEN say('  none'); END IF;

	section('Storage headroom (the copy briefly needs extra space)');
	BEGIN
		SELECT NVL(SUM(bytes), 0) INTO l_count
		  FROM user_segments WHERE segment_name = l_table;
		say('  Table segment size: ' || ROUND(l_count / 1024 / 1024) || ' MB');
		SELECT tablespace_name INTO l_text
		  FROM user_tables WHERE table_name = l_table;
		say('  Tablespace: ' || NVL(l_text, '(default)'));
		BEGIN
			SELECT NVL(SUM(bytes), 0) INTO l_count2
			  FROM user_free_space WHERE tablespace_name = l_text;
			say('  Free in tablespace: ' || ROUND(l_count2 / 1024 / 1024) || ' MB'
				|| CASE WHEN l_count2 < l_count THEN '  <-- less than table size, tell us' ELSE '' END);
		EXCEPTION WHEN OTHERS THEN
			say('  free space not visible to this user');
		END;
	EXCEPTION WHEN OTHERS THEN
		say('  not visible to this user');
	END;

	section('Replication indicators (best effort)');
	BEGIN
		EXECUTE IMMEDIATE
			'SELECT supplemental_log_data_min FROM v$database' INTO l_text;
		say('  Supplemental logging (minimal): ' || l_text
			|| CASE WHEN l_text <> 'NO' THEN '  <-- replication may be configured, tell us' ELSE '' END);
	EXCEPTION WHEN OTHERS THEN
		say('  v$database not visible to this user - please confirm with your '
			|| 'DBA whether this database is replicated (GoldenGate, standby, CDC)');
	END;

	say('');
	say('PREFLIGHT COMPLETE - no changes were made. Please send back '
		|| 'nc_ora22858_preflight.log.');
EXCEPTION WHEN OTHERS THEN
	say('');
	say('PREFLIGHT INTERNAL ERROR (diagnostics incomplete, still no changes '
		|| 'were made): ' || SQLERRM);
	say('Please send back nc_ora22858_preflight.log anyway.');
END;
/

SPOOL OFF
EXIT

<?php /* vim: se et ts=4 sw=4 sts=4 fdm=marker tw=80: */
/**
 * Copyright (c) 1998-2010 Manuel Lemos, Tomas V.V.Cox,
 * Stig. S. Bakken, Lukas Smith, Igor Feghali
 * All rights reserved.
 *
 * MDB2_Schema enables users to maintain RDBMS independant schema files
 * in XML that can be used to manipulate both data and database schemas
 * This LICENSE is in the BSD license style.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,
 * Lukas Smith, Igor Feghali nor the names of his contributors may be
 * used to endorse or promote products derived from this software
 * without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE
 * REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 *  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
 * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP version 5
 *
 * @category Database
 * @package  MDB2_Schema
 * @author   David Coallier <davidc@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @version  SVN: $Id$
 * @link     http://pear.php.net/packages/MDB2_Schema
 */

// {{{ $GLOBALS['_MDB2_Schema_Reserved']['mssql']
/**
 * Has a list of all the reserved words for mssql.
 *
 * @package  MDB2_Schema
 * @category Database
 * @access   protected
 * @author   David Coallier <davidc@php.net>
 */
$GLOBALS['_MDB2_Schema_Reserved']['mssql'] = array(
    'ADD',
    'CURRENT_TIMESTAMP',
    'GROUP',
    'OPENQUERY',
    'SERIALIZABLE',
    'ALL',
    'CURRENT_USER',
    'HAVING',
    'OPENROWSET',
    'SESSION_USER',
    'ALTER',
    'CURSOR',
    'HOLDLOCK',
    'OPTION',
    'SET',
    'AND',
    'DATABASE',
    'IDENTITY',
    'OR',
    'SETUSER',
    'ANY',
    'DBCC',
    'IDENTITYCOL',
    'ORDER',
    'SHUTDOWN',
    'AS',
    'DEALLOCATE',
    'IDENTITY_INSERT',
    'OUTER',
    'SOME',
    'ASC',
    'DECLARE',
    'IF',
    'OVER',
    'STATISTICS',
    'AUTHORIZATION',
    'DEFAULT',
    'IN',
    'PERCENT',
    'SUM',
    'AVG',
    'DELETE',
    'INDEX',
    'PERM',
    'SYSTEM_USER',
    'BACKUP',
    'DENY',
    'INNER',
    'PERMANENT',
    'TABLE',
    'BEGIN',
    'DESC',
    'INSERT',
    'PIPE',
    'TAPE',
    'BETWEEN',
    'DISK',
    'INTERSECT',
    'PLAN',
    'TEMP',
    'BREAK',
    'DISTINCT',
    'INTO',
    'PRECISION',
    'TEMPORARY',
    'BROWSE',
    'DISTRIBUTED',
    'IS',
    'PREPARE',
    'TEXTSIZE',
    'BULK',
    'DOUBLE',
    'ISOLATION',
    'PRIMARY',
    'THEN',
    'BY',
    'DROP',
    'JOIN',
    'PRINT',
    'TO',
    'CASCADE',
    'DUMMY',
    'KEY',
    'PRIVILEGES',
    'TOP',
    'CASE',
    'DUMP',
    'KILL',
    'PROC',
    'TRAN',
    'CHECK',
    'ELSE',
    'LEFT',
    'PROCEDURE',
    'TRANSACTION',
    'CHECKPOINT',
    'END',
    'LEVEL',
    'PROCESSEXIT',
    'TRIGGER',
    'CLOSE',
    'ERRLVL',
    'LIKE',
    'PUBLIC',
    'TRUNCATE',
    'CLUSTERED',
    'ERROREXIT',
    'LINENO',
    'RAISERROR',
    'TSEQUAL',
    'COALESCE',
    'ESCAPE',
    'LOAD',
    'READ',
    'UNCOMMITTED',
    'COLUMN',
    'EXCEPT',
    'MAX',
    'READTEXT',
    'UNION',
    'COMMIT',
    'EXEC',
    'MIN',
    'RECONFIGURE',
    'UNIQUE',
    'COMMITTED',
    'EXECUTE',
    'MIRROREXIT',
    'REFERENCES',
    'UPDATE',
    'COMPUTE',
    'EXISTS',
    'NATIONAL',
    'REPEATABLE',
    'UPDATETEXT',
    'CONFIRM',
    'EXIT',
    'NOCHECK',
    'REPLICATION',
    'USE',
    'CONSTRAINT',
    'FETCH',
    'NONCLUSTERED',
    'RESTORE',
    'USER',
    'CONTAINS',
    'FILE',
    'NOT',
    'RESTRICT',
    'VALUES',
    'CONTAINSTABLE',
    'FILLFACTOR',
    'NULL',
    'RETURN',
    'VARYING',
    'CONTINUE',
    'FLOPPY',
    'NULLIF',
    'REVOKE',
    'VIEW',
    'CONTROLROW',
    'FOR',
    'OF',
    'RIGHT',
    'WAITFOR',
    'CONVERT',
    'FOREIGN',
    'OFF',
    'ROLLBACK',
    'WHEN',
    'COUNT',
    'FREETEXT',
    'OFFSETS',
    'ROWCOUNT',
    'WHERE',
    'CREATE',
    'FREETEXTTABLE',
    'ON',
    'ROWGUIDCOL',
    'WHILE',
    'CROSS',
    'FROM',
    'ONCE',
    'RULE',
    'WITH',
    'CURRENT',
    'FULL',
    'ONLY',
    'SAVE',
    'WORK',
    'CURRENT_DATE',
    'GOTO',
    'OPEN',
    'SCHEMA',
    'WRITETEXT',
    'CURRENT_TIME',
    'GRANT',
    'OPENDATASOURCE',
    'SELECT',
);
//}}}

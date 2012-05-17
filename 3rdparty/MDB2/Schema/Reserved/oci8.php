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

// {{{ $GLOBALS['_MDB2_Schema_Reserved']['oci8']
/**
 * Has a list of all the reserved words for oracle.
 *
 * @package  MDB2_Schema
 * @category Database
 * @access   protected
 * @author   David Coallier <davidc@php.net>
 */
$GLOBALS['_MDB2_Schema_Reserved']['oci8'] = array(
    'ACCESS',
    'ELSE',
    'MODIFY',
    'START',
    'ADD',
    'EXCLUSIVE',
    'NOAUDIT',
    'SELECT',
    'ALL',
    'EXISTS',
    'NOCOMPRESS',
    'SESSION',
    'ALTER',
    'FILE',
    'NOT',
    'SET',
    'AND',
    'FLOAT',
    'NOTFOUND ',
    'SHARE',
    'ANY',
    'FOR',
    'NOWAIT',
    'SIZE',
    'ARRAYLEN',
    'FROM',
    'NULL',
    'SMALLINT',
    'AS',
    'GRANT',
    'NUMBER',
    'SQLBUF',
    'ASC',
    'GROUP',
    'OF',
    'SUCCESSFUL',
    'AUDIT',
    'HAVING',
    'OFFLINE ',
    'SYNONYM',
    'BETWEEN',
    'IDENTIFIED',
    'ON',
    'SYSDATE',
    'BY',
    'IMMEDIATE',
    'ONLINE',
    'TABLE',
    'CHAR',
    'IN',
    'OPTION',
    'THEN',
    'CHECK',
    'INCREMENT',
    'OR',
    'TO',
    'CLUSTER',
    'INDEX',
    'ORDER',
    'TRIGGER',
    'COLUMN',
    'INITIAL',
    'PCTFREE',
    'UID',
    'COMMENT',
    'INSERT',
    'PRIOR',
    'UNION',
    'COMPRESS',
    'INTEGER',
    'PRIVILEGES',
    'UNIQUE',
    'CONNECT',
    'INTERSECT',
    'PUBLIC',
    'UPDATE',
    'CREATE',
    'INTO',
    'RAW',
    'USER',
    'CURRENT',
    'IS',
    'RENAME',
    'VALIDATE',
    'DATE',
    'LEVEL',
    'RESOURCE',
    'VALUES',
    'DECIMAL',
    'LIKE',
    'REVOKE',
    'VARCHAR',
    'DEFAULT',
    'LOCK',
    'ROW',
    'VARCHAR2',
    'DELETE',
    'LONG',
    'ROWID',
    'VIEW',
    'DESC',
    'MAXEXTENTS',
    'ROWLABEL',
    'WHENEVER',
    'DISTINCT',
    'MINUS',
    'ROWNUM',
    'WHERE',
    'DROP',
    'MODE',
    'ROWS',
    'WITH',
);
// }}}

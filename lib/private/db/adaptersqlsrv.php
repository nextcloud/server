<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace OC\DB;

class AdapterSQLSrv extends Adapter {
	public function fixupStatement($statement) {
		$statement = str_replace(' ILIKE ', ' COLLATE Latin1_General_CI_AS LIKE ', $statement);
		$statement = preg_replace( "/\`(.*?)`/", "[$1]", $statement );
		$statement = str_ireplace( 'NOW()', 'CURRENT_TIMESTAMP', $statement );
		$statement = str_replace( 'LENGTH(', 'LEN(', $statement );
		$statement = str_replace( 'SUBSTR(', 'SUBSTRING(', $statement );
		$statement = str_ireplace( 'UNIX_TIMESTAMP()', 'DATEDIFF(second,{d \'1970-01-01\'},GETDATE())', $statement );
		return $statement;
	}
}

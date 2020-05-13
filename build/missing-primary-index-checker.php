<?php
/**
 * @copyright Copyright (c) 2020 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$sql = "
SELECT
  tab.table_schema as database_name,
  tab.table_name
FROM
  information_schema.tables tab
LEFT JOIN
  information_schema.table_constraints tco
  ON tab.table_schema = tco.table_schema
    AND tab.table_name = tco.table_name
    AND tco.constraint_type = 'PRIMARY KEY'
WHERE
  tco.constraint_type IS NULL
  AND tab.table_schema NOT IN('mysql', 'information_schema', 'performance_schema', 'sys')
  AND tab.table_type = 'BASE TABLE'
ORDER BY tab.table_schema, tab.table_name;
";

include __DIR__ . '/../config/config.php';
$host = $CONFIG['dbhost'];
$dbname = $CONFIG['dbname'];
$username = $CONFIG['dbuser'];
$password = $CONFIG['dbpassword'];

$pdo = new \PDO("mysql:host=$host;dbname=$dbname", $username, $password);

$missingPrimaryKeys = false;
foreach ($pdo->query($sql) as $row) {
	echo $row['database_name'] . ' ' . $row['table_name'] . PHP_EOL;
	$missingPrimaryKeys = true;
}

if ($missingPrimaryKeys) {
	echo "There are tables with missing primary keys in the DB. Please make sure those tables all have a proper primary key.\n";
	exit(1);
}

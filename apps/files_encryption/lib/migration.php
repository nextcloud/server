<?php
 /**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Encryption;


class Migration {

	public function __construct($tableName = 'encryption') {
		$this->tableName = $tableName;
	}

	// migrate settings from oc_encryption to oc_preferences
	public function dropTableEncryption() {
		$tableName = $this->tableName;
		if (!\OC_DB::tableExists($tableName)) {
			return;
		}
		$sql = "select `uid`, max(`recovery_enabled`) as `recovery_enabled`, min(`migration_status`) as `migration_status` from `*PREFIX*$tableName` group by `uid`";
		$query = \OCP\DB::prepare($sql);
		$result = $query->execute(array())->fetchAll();

		foreach ($result as $row) {
			\OC_Preferences::setValue($row['uid'], 'files_encryption', 'recovery_enabled', $row['recovery_enabled']);
			\OC_Preferences::setValue($row['uid'], 'files_encryption', 'migration_status', $row['migration_status']);
		}

		\OC_DB::dropTable($tableName);
	}

}

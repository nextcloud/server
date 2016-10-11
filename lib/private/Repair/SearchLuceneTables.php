<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Repair;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class SearchLuceneTables implements IRepairStep {

	public function getName() {
		return 'Repair duplicate entries in oc_lucene_status';
	}

	/**
	 * Fix duplicate entries in oc_lucene_status
	 *
	 * search_lucene prior to v0.5.0 did not have a primary key on the lucene_status table. Newer versions do, which
	 * causes the migration check to fail because it tries to insert duplicate rows into the new schema.
	 *
	 * FIXME Currently, apps don't have a way of repairing anything before the migration check:
	 * @link https://github.com/owncloud/core/issues/10980
	 *
	 * As a result this repair step needs to live in the core repo, although it belongs into search_lucene:
	 * @link https://github.com/owncloud/core/issues/10205#issuecomment-54957557
	 *
	 * It will completely remove any rows that make a file id have more than one status:
	 *  fileid | status                       fileid | status
	 * --------+--------     will become     --------+--------
	 *     2   |   E                             3   |   E
	 *     2   |   I
	 *     3   |   E
	 *
	 * search_lucene will then reindex the fileids without a status when the next indexing job is executed
	 */
	public function run(IOutput $out) {
		$connection = \OC::$server->getDatabaseConnection();
		if ($connection->tableExists('lucene_status')) {
			$out->info('removing duplicate entries from lucene_status');

			$query = $connection->prepare('
				DELETE FROM `*PREFIX*lucene_status`
				WHERE `fileid` IN (
					SELECT `fileid`
					FROM (
						SELECT `fileid`
						FROM `*PREFIX*lucene_status`
						GROUP BY `fileid`
						HAVING count(`fileid`) > 1
					) AS `mysqlerr1093hack`
				)');
			$query->execute();
		} else {
			$out->info('lucene_status table does not exist -> nothing to do');
		}
	}

}


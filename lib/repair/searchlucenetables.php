<?php
/**
 * Copyright (c) 2014 Jörn Friedrich Dreyer <jfd@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Repair;

use OC\Hooks\BasicEmitter;

class SearchLuceneTables extends BasicEmitter implements \OC\RepairStep {

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
	public function run() {
		if (\OC_DB::tableExists('lucene_status')) {
			$this->emit('\OC\Repair', 'info', array('removing duplicate entries from lucene_status'));

			$connection = \OC_DB::getConnection();
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
			$this->emit('\OC\Repair', 'info', array('lucene_status table does not exist -> nothing to do'));
		}
	}

}


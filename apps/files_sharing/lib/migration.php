<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

 namespace OCA\Files_Sharing;

class Migration {


	/**
	 * set accepted to 1 for all external shares. At this point in time we only
	 * have shares from the first version of server-to-server sharing so all should
	 * be accepted
	 */
	public function addAcceptRow() {
		$statement = 'UPDATE `*PREFIX*share_external` SET `accepted` = 1';
		$connection = \OC::$server->getDatabaseConnection();
		$query = $connection->prepare($statement);
		$query->execute();
	}


}

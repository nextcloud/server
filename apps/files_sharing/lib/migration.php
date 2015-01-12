<?php
 /**
 * ownCloud - migration to new version of the files sharing app
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
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

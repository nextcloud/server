<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCP\Diagnostics;

use Doctrine\DBAL\Logging\SQLLogger;

/**
 * Interface IQueryLogger
 *
 * @package OCP\Diagnostics
 * @since 8.0.0
 */
interface IQueryLogger extends SQLLogger {
	/**
	 * @param string $sql
	 * @param array $params
	 * @param array $types
	 * @since 8.0.0
	 */
	public function startQuery($sql, array $params = null, array $types = null);

	/**
	 * @return mixed
	 * @since 8.0.0
	 */
	public function stopQuery();

	/**
	 * @return \OCP\Diagnostics\IQuery[]
	 * @since 8.0.0
	 */
	public function getQueries();
}

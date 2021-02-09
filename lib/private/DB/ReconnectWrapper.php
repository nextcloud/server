<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Robin Appelman <robin@icewind.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\DB;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;

class ReconnectWrapper extends \Doctrine\DBAL\Connection {
	public const CHECK_CONNECTION_INTERVAL = 60;

	private $lastQuery = null;

	public function __construct(array $params, Driver $driver, Configuration $config = null, EventManager $eventManager = null) {
		parent::__construct($params, $driver, $config, $eventManager);
		$this->lastQuery = time();
	}

	public function connect() {
		$now = time();
		$checkTime = $now - self::CHECK_CONNECTION_INTERVAL;

		if ($this->lastQuery > $checkTime || $this->isTransactionActive()) {
			$this->lastQuery = $now;
			return parent::connect();
		} else {
			$this->lastQuery = $now;
			if (!$this->ping()) {
				$this->close();
			}
			return parent::connect();
		}
	}
}

<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OC\Repair;

use OC\Hooks\BasicEmitter;

/**
 * Repairs shares with invalid data
 */
class RepairInvalidShares extends BasicEmitter implements \OC\RepairStep {

	/**
	 * @var \OCP\IConfig
	 */
	protected $config;

	/**
	 * @var \OCP\IDBConnection
	 */
	protected $connection;

	/**
	 * @param \OCP\IConfig $config
	 * @param \OCP\IDBConnection $connection
	 */
	public function __construct($config, $connection) {
		$this->connection = $connection;
		$this->config = $config;
	}

	public function getName() {
		return 'Repair invalid shares';
	}

	/**
	 * Past bugs would make it possible to set an expiration date on user shares even
	 * though it is not supported. This functions removes the expiration date from such entries.
	 */
	private function removeExpirationDateFromNonLinkShares() {
		$builder = $this->connection->getQueryBuilder();
		$builder
			->update('share')
			->set('expiration', 'null')
			->where($builder->expr()->isNotNull('expiration'))
			->andWhere($builder->expr()->neq('share_type', $builder->expr()->literal(\OC\Share\Constants::SHARE_TYPE_LINK)));

		$updatedEntries = $builder->execute();
		if ($updatedEntries > 0) {
			$this->emit('\OC\Repair', 'info', array('Removed invalid expiration date from ' . $updatedEntries . ' shares'));
		}
	}

	public function run() {
		$ocVersionFromBeforeUpdate = $this->config->getSystemValue('version', '0.0.0');
		if (version_compare($ocVersionFromBeforeUpdate, '8.2.0.7', '<')) {
			// this situation was only possible before 8.2
			$this->removeExpirationDateFromNonLinkShares();
		}
	}
}

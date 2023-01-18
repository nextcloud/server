<?php
/**
 * @copyright Copyright (c) 2023, Nextcloud GmbH.
 *
 * @author Simon Spannagel <simonspa@kth.se>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Repair;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RepairUnencryptedSize implements IRepairStep {
	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	public function getName() {
		return 'Repair file size of unencrypted files';
	}

    /**
	 * Fix unencrypted file size
	 */
	public function run(IOutput $output) {

        $update = $this->connection->getQueryBuilder();
		$update->update('filecache')
			->set('unencrypted_size', "0")
			->where($update->expr()->eq('encrypted',"0", IQueryBuilder::PARAM_INT));

        if ($update->execute()) {
            $output->info('Fixed file size of unencrypted files');
        }
	}
}

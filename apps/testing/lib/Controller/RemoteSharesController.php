<?php
/**
 * @copyright Copyright (c) 2024, Daniel Calviño Sánchez <danxuliu@gmail.com>
 *
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
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
namespace OCA\Testing\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IDBConnection;
use OCP\IRequest;

class RemoteSharesController extends OCSController {

	/** @var IDBConnection */
	protected $connection;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 */
	public function __construct($appName,
		IRequest $request,
		IDBConnection $connection) {
		parent::__construct($appName, $request);
		$this->connection = $connection;
	}

	/**
	 * Deletes all remote shares.
	 *
	 * Note that neither storages nor mountpoints are deleted. This is meant to
	 * be used to get rid of dangling group shares after the users and groups
	 * were deleted.
	 *
	 * @return DataResponse
	 */
	public function delete() {
		// For simplicity the database table is cleared directly in the
		// controller :see-no-evil:
		$query = $this->connection->getQueryBuilder();
		$query->delete('share_external');

		$query->executeStatement();

		return new DataResponse();
	}
}

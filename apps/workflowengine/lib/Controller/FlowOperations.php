<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\WorkflowEngine\Controller;

use OCA\WorkflowEngine\Manager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class FlowOperations extends Controller {

	/** @var Manager */
	protected $manager;

	/**
	 * @param IRequest $request
	 * @param Manager $manager
	 */
	public function __construct(IRequest $request, Manager $manager) {
		parent::__construct('workflowengine', $request);
		$this->manager = $manager;
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @param string $class
	 * @return JSONResponse
	 */
	public function getOperations($class) {
		$operations = $this->manager->getOperations($class);

		foreach ($operations as &$operation) {
			$operation = $this->prepareOperation($operation);
		}

		return new JSONResponse($operations);
	}

	/**
	 * @PasswordConfirmationRequired
	 *
	 * @param string $class
	 * @param string $name
	 * @param array[] $checks
	 * @param string $operation
	 * @return JSONResponse The added element
	 */
	public function addOperation($class, $name, $checks, $operation) {
		try {
			$operation = $this->manager->addOperation($class, $name, $checks, $operation);
			$operation = $this->prepareOperation($operation);
			return new JSONResponse($operation);
		} catch (\UnexpectedValueException $e) {
			return new JSONResponse($e->getMessage(), Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * @PasswordConfirmationRequired
	 *
	 * @param int $id
	 * @param string $name
	 * @param array[] $checks
	 * @param string $operation
	 * @return JSONResponse The updated element
	 */
	public function updateOperation($id, $name, $checks, $operation) {
		try {
			$operation = $this->manager->updateOperation($id, $name, $checks, $operation);
			$operation = $this->prepareOperation($operation);
			return new JSONResponse($operation);
		} catch (\UnexpectedValueException $e) {
			return new JSONResponse($e->getMessage(), Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * @PasswordConfirmationRequired
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function deleteOperation($id) {
		$deleted = $this->manager->deleteOperation((int) $id);
		return new JSONResponse($deleted);
	}

	/**
	 * @param array $operation
	 * @return array
	 */
	protected function prepareOperation(array $operation) {
		$checkIds = json_decode($operation['checks'], true);
		$checks = $this->manager->getChecks($checkIds);

		$operation['checks'] = [];
		foreach ($checks as $check) {
			// Remove internal values
			unset($check['id']);
			unset($check['hash']);

			$operation['checks'][] = $check;
		}

		return $operation;
	}
}

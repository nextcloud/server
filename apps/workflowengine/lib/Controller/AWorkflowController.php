<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Controller;

use Doctrine\DBAL\Exception;
use OCA\WorkflowEngine\Helper\ScopeContext;
use OCA\WorkflowEngine\Manager;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

abstract class AWorkflowController extends OCSController {

	public function __construct(
		$appName,
		IRequest $request,
		protected Manager $manager,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @throws OCSForbiddenException
	 */
	abstract protected function getScopeContext(): ScopeContext;

	/**
	 * Example: curl -u joann -H "OCS-APIREQUEST: true" "http://my.nc.srvr/ocs/v2.php/apps/workflowengine/api/v1/workflows/global?format=json"
	 *
	 * @throws OCSForbiddenException
	 */
	public function index(): DataResponse {
		$operationsByClass = $this->manager->getAllOperations($this->getScopeContext());

		foreach ($operationsByClass as &$operations) {
			foreach ($operations as &$operation) {
				$operation = $this->manager->formatOperation($operation);
			}
		}

		return new DataResponse($operationsByClass);
	}

	/**
	 * Example: curl -u joann -H "OCS-APIREQUEST: true" "http://my.nc.srvr/ocs/v2.php/apps/workflowengine/api/v1/workflows/global/OCA\\Workflow_DocToPdf\\Operation?format=json"
	 *
	 * @throws OCSForbiddenException
	 */
	public function show(string $id): DataResponse {
		$context = $this->getScopeContext();

		// The ID corresponds to a class name
		$operations = $this->manager->getOperations($id, $context);

		foreach ($operations as &$operation) {
			$operation = $this->manager->formatOperation($operation);
		}

		return new DataResponse($operations);
	}

	/**
	 * @throws OCSBadRequestException
	 * @throws OCSForbiddenException
	 * @throws OCSException
	 */
	#[PasswordConfirmationRequired]
	public function create(
		string $class,
		string $name,
		array $checks,
		string $operation,
		string $entity,
		array $events,
	): DataResponse {
		$context = $this->getScopeContext();
		try {
			$operation = $this->manager->addOperation($class, $name, $checks, $operation, $context, $entity, $events);
			$operation = $this->manager->formatOperation($operation);
			return new DataResponse($operation);
		} catch (\UnexpectedValueException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		} catch (\DomainException $e) {
			throw new OCSForbiddenException($e->getMessage(), $e);
		} catch (Exception $e) {
			$this->logger->error('Error when inserting flow', ['exception' => $e]);
			throw new OCSException('An internal error occurred', $e->getCode(), $e);
		}
	}

	/**
	 * @throws OCSBadRequestException
	 * @throws OCSForbiddenException
	 * @throws OCSException
	 */
	#[PasswordConfirmationRequired]
	public function update(
		int $id,
		string $name,
		array $checks,
		string $operation,
		string $entity,
		array $events,
	): DataResponse {
		try {
			$context = $this->getScopeContext();
			$operation = $this->manager->updateOperation($id, $name, $checks, $operation, $context, $entity, $events);
			$operation = $this->manager->formatOperation($operation);
			return new DataResponse($operation);
		} catch (\UnexpectedValueException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		} catch (\DomainException $e) {
			throw new OCSForbiddenException($e->getMessage(), $e);
		} catch (Exception $e) {
			$this->logger->error('Error when updating flow with id ' . $id, ['exception' => $e]);
			throw new OCSException('An internal error occurred', $e->getCode(), $e);
		}
	}

	/**
	 * @throws OCSBadRequestException
	 * @throws OCSForbiddenException
	 * @throws OCSException
	 */
	#[PasswordConfirmationRequired]
	public function destroy(int $id): DataResponse {
		try {
			$deleted = $this->manager->deleteOperation($id, $this->getScopeContext());
			return new DataResponse($deleted);
		} catch (\UnexpectedValueException $e) {
			throw new OCSBadRequestException($e->getMessage(), $e);
		} catch (\DomainException $e) {
			throw new OCSForbiddenException($e->getMessage(), $e);
		} catch (Exception $e) {
			$this->logger->error('Error when deleting flow with id ' . $id, ['exception' => $e]);
			throw new OCSException('An internal error occurred', $e->getCode(), $e);
		}
	}
}

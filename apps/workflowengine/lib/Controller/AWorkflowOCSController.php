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
use OCA\WorkflowEngine\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\WorkflowEngine\IEntityEvent;
use OCP\WorkflowEngine\IOperation;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type WorkflowEngineOperator from ResponseDefinitions
 * @psalm-import-type WorkflowEngineCheck from ResponseDefinitions
 * @psalm-import-type WorkflowEngineRule from ResponseDefinitions
 */
abstract class AWorkflowOCSController extends OCSController {

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
	 * Retrieve all configured workflow rules
	 *
	 * @return DataResponse<Http::STATUS_OK, array<class-string<IOperation>, list<WorkflowEngineRule>>, array{}>
	 *
	 * 200: List of workflows returned
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
	 * Retrieve a specific workflow
	 *
	 * @param string $id Workflow ID to load
	 * @return DataResponse<Http::STATUS_OK, WorkflowEngineRule|list<empty>, array{}>
	 *
	 * 200: Workflow returned or empty array if the ID is unknown in the scope
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
	 * Create a workflow
	 *
	 * @param class-string<IOperation> $class Operation class to execute
	 * @param string $name Name of the workflow rule
	 * @param list<WorkflowEngineCheck> $checks List of conditions that need to apply for the rule to match
	 * @param string $operation Operation class to execute on match
	 * @param string $entity The matched entity
	 * @param list<class-string<IEntityEvent>> $events The list of events on which the rule should be validated
	 * @return DataResponse<Http::STATUS_OK, WorkflowEngineRule, array{}>
	 *
	 * 200: Workflow created
	 *
	 * @throws OCSBadRequestException Thrown when a check or check value is invalid
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
	 * Modify a workflow
	 *
	 * @param int $id Workflow ID to delete
	 * @param string $name Name of the workflow rule
	 * @param list<WorkflowEngineCheck> $checks List of conditions that need to apply for the rule to match
	 * @param string $operation Operation action to execute on match
	 * @param string $entity The matched entity
	 * @param list<class-string<IEntityEvent>> $events The list of events on which the rule should be validated
	 * @return DataResponse<Http::STATUS_OK, WorkflowEngineRule, array{}>
	 *
	 * 200: Workflow updated
	 *
	 * @throws OCSBadRequestException Thrown when a check or check value is invalid
	 * @throws OCSForbiddenException Thrown when workflow is from a different scope
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
	 * Delete a workflow
	 *
	 * @param int $id Workflow ID to delete
	 * @return DataResponse<Http::STATUS_OK, bool, array{}>|DataResponse<Http::STATUS_FORBIDDEN, list<empty>, array{}>
	 *
	 * 200: Workflow deleted
	 *
	 * @throws OCSForbiddenException Thrown when workflow is from a different scope
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

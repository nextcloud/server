<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Controller;

use OCA\WorkflowEngine\Helper\ScopeContext;
use OCA\WorkflowEngine\Manager;
use OCA\WorkflowEngine\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\WorkflowEngine\IEntityEvent;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IOperation;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type WorkflowEngineCheck from ResponseDefinitions
 * @psalm-import-type WorkflowEngineRule from ResponseDefinitions
 */
class UserWorkflowsController extends AWorkflowOCSController {

	/** @var ScopeContext */
	private $scopeContext;

	public function __construct(
		$appName,
		IRequest $request,
		Manager $manager,
		private IUserSession $session,
		LoggerInterface $logger,
	) {
		parent::__construct($appName, $request, $manager, $logger);
	}

	/**
	 * Retrieve all configured workflow rules
	 *
	 * @return DataResponse<Http::STATUS_OK, array<class-string<IOperation>, list<WorkflowEngineRule>>, array{}>
	 *
	 * 200: List of workflows returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/workflows/user')]
	public function index(): DataResponse {
		return parent::index();
	}

	/**
	 * Retrieve a specific workflow
	 *
	 * @param string $id Workflow ID to load
	 * @return DataResponse<Http::STATUS_OK, WorkflowEngineRule|list<empty>, array{}>
	 *
	 * 200: Workflow returned or empty array if the ID is unknown in the scope
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/workflows/user/{id}')]
	public function show(string $id): DataResponse {
		return parent::show($id);
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
	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1/workflows/user')]
	public function create(string $class, string $name, array $checks, string $operation, string $entity, array $events): DataResponse {
		return parent::create($class, $name, $checks, $operation, $entity, $events);
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
	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/workflows/user/{id}')]
	public function update(int $id, string $name, array $checks, string $operation, string $entity, array $events): DataResponse {
		return parent::update($id, $name, $checks, $operation, $entity, $events);
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
	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1/workflows/user/{id}')]
	public function destroy(int $id): DataResponse {
		return parent::destroy($id);
	}

	/**
	 * @throws OCSForbiddenException
	 */
	protected function getScopeContext(): ScopeContext {
		if ($this->scopeContext === null) {
			$user = $this->session->getUser();
			if (!$user || !$this->manager->isUserScopeEnabled()) {
				throw new OCSForbiddenException('User not logged in');
			}
			$this->scopeContext = new ScopeContext(IManager::SCOPE_USER, $user->getUID());
		}
		return $this->scopeContext;
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Controller;

use OCA\WorkflowEngine\Helper\ScopeContext;
use OCA\WorkflowEngine\Manager;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\WorkflowEngine\IManager;
use Psr\Log\LoggerInterface;

class UserWorkflowsController extends AWorkflowController {

	/** @var IUserSession */
	private $session;

	/** @var ScopeContext */
	private $scopeContext;

	public function __construct(
		$appName,
		IRequest $request,
		Manager $manager,
		IUserSession $session,
		LoggerInterface $logger
	) {
		parent::__construct($appName, $request, $manager, $logger);

		$this->session = $session;
	}

	/**
	 * Retrieve all configured workflow rules
	 *
	 * Example: curl -u joann -H "OCS-APIREQUEST: true" "http://my.nc.srvr/ocs/v2.php/apps/workflowengine/api/v1/workflows/user?format=json"
	 *
	 * @NoAdminRequired
	 * @throws OCSForbiddenException
	 */
	public function index(): DataResponse {
		return parent::index();
	}

	/**
	 * @NoAdminRequired
	 *
	 * Example: curl -u joann -H "OCS-APIREQUEST: true" "http://my.nc.srvr/ocs/v2.php/apps/workflowengine/api/v1/workflows/user/OCA\\Workflow_DocToPdf\\Operation?format=json"
	 * @throws OCSForbiddenException
	 */
	public function show(string $id): DataResponse {
		return parent::show($id);
	}

	/**
	 * @NoAdminRequired
	 * @throws OCSBadRequestException
	 * @throws OCSForbiddenException
	 */
	#[PasswordConfirmationRequired]
	public function create(string $class, string $name, array $checks, string $operation, string $entity, array $events): DataResponse {
		return parent::create($class, $name, $checks, $operation, $entity, $events);
	}

	/**
	 * @NoAdminRequired
	 * @throws OCSBadRequestException
	 * @throws OCSForbiddenException
	 */
	#[PasswordConfirmationRequired]
	public function update(int $id, string $name, array $checks, string $operation, string $entity, array $events): DataResponse {
		return parent::update($id, $name, $checks, $operation, $entity, $events);
	}

	/**
	 * @NoAdminRequired
	 * @throws OCSForbiddenException
	 */
	#[PasswordConfirmationRequired]
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

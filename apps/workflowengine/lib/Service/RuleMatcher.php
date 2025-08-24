<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Service;

use OCA\WorkflowEngine\Helper\LogContext;
use OCA\WorkflowEngine\Helper\ScopeContext;
use OCA\WorkflowEngine\Manager;
use OCP\AppFramework\QueryException;
use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use OCP\IServerContainer;
use OCP\IUserSession;
use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IEntityCheck;
use OCP\WorkflowEngine\IFileCheck;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IOperation;
use OCP\WorkflowEngine\IRuleMatcher;
use RuntimeException;

class RuleMatcher implements IRuleMatcher {

	/** @var array */
	protected $contexts;
	/** @var array */
	protected $fileInfo = [];
	/** @var IOperation */
	protected $operation;
	/** @var IEntity */
	protected $entity;
	/** @var string */
	protected $eventName;

	public function __construct(
		protected IUserSession $session,
		protected IServerContainer $container,
		protected IL10N $l,
		protected Manager $manager,
		protected Logger $logger,
	) {
	}

	public function setFileInfo(IStorage $storage, string $path, bool $isDir = false): void {
		$this->fileInfo['storage'] = $storage;
		$this->fileInfo['path'] = $path;
		$this->fileInfo['isDir'] = $isDir;
	}

	public function setEntitySubject(IEntity $entity, $subject): void {
		$this->contexts[get_class($entity)] = [$entity, $subject];
	}

	public function setOperation(IOperation $operation): void {
		if ($this->operation !== null) {
			throw new RuntimeException('This method must not be called more than once');
		}
		$this->operation = $operation;
	}

	public function setEntity(IEntity $entity): void {
		if ($this->entity !== null) {
			throw new RuntimeException('This method must not be called more than once');
		}
		$this->entity = $entity;
	}

	public function setEventName(string $eventName): void {
		if ($this->eventName !== null) {
			throw new RuntimeException('This method must not be called more than once');
		}
		$this->eventName = $eventName;
	}

	public function getEntity(): IEntity {
		if ($this->entity === null) {
			throw new \LogicException('Entity was not set yet');
		}
		return $this->entity;
	}

	public function getFlows(bool $returnFirstMatchingOperationOnly = true): array {
		if (!$this->operation) {
			throw new RuntimeException('Operation is not set');
		}
		return $this->getMatchingOperations(get_class($this->operation), $returnFirstMatchingOperationOnly);
	}

	public function getMatchingOperations(string $class, bool $returnFirstMatchingOperationOnly = true): array {
		$scopes[] = new ScopeContext(IManager::SCOPE_ADMIN);
		$user = $this->session->getUser();
		if ($user !== null && $this->manager->isUserScopeEnabled()) {
			$scopes[] = new ScopeContext(IManager::SCOPE_USER, $user->getUID());
		}

		$ctx = new LogContext();
		$ctx
			->setScopes($scopes)
			->setEntity($this->entity)
			->setOperation($this->operation);
		$this->logger->logFlowRequests($ctx);

		$operations = [];
		foreach ($scopes as $scope) {
			$operations = array_merge($operations, $this->manager->getOperations($class, $scope));
		}

		if ($this->entity instanceof IEntity) {
			/** @var ScopeContext[] $additionalScopes */
			$additionalScopes = $this->manager->getAllConfiguredScopesForOperation($class);
			foreach ($additionalScopes as $hash => $scopeCandidate) {
				if ($scopeCandidate->getScope() !== IManager::SCOPE_USER || in_array($scopeCandidate, $scopes)) {
					continue;
				}
				if ($this->entity->isLegitimatedForUserId($scopeCandidate->getScopeId())) {
					$ctx = new LogContext();
					$ctx
						->setScopes([$scopeCandidate])
						->setEntity($this->entity)
						->setOperation($this->operation);
					$this->logger->logScopeExpansion($ctx);
					$operations = array_merge($operations, $this->manager->getOperations($class, $scopeCandidate));
				}
			}
		}

		$matches = [];
		foreach ($operations as $operation) {
			$configuredEvents = json_decode($operation['events'], true);
			if ($this->eventName !== null && !in_array($this->eventName, $configuredEvents)) {
				continue;
			}

			$checkIds = json_decode($operation['checks'], true);
			$checks = $this->manager->getChecks($checkIds);

			foreach ($checks as $check) {
				if (!$this->check($check)) {
					// Check did not match, continue with the next operation
					continue 2;
				}
			}

			$ctx = new LogContext();
			$ctx
				->setEntity($this->entity)
				->setOperation($this->operation)
				->setConfiguration($operation);
			$this->logger->logPassedCheck($ctx);

			if ($returnFirstMatchingOperationOnly) {
				$ctx = new LogContext();
				$ctx
					->setEntity($this->entity)
					->setOperation($this->operation)
					->setConfiguration($operation);
				$this->logger->logRunSingle($ctx);
				return $operation;
			}
			$matches[] = $operation;
		}

		$ctx = new LogContext();
		$ctx
			->setEntity($this->entity)
			->setOperation($this->operation);
		if (!empty($matches)) {
			$ctx->setConfiguration($matches);
			$this->logger->logRunAll($ctx);
		} else {
			$this->logger->logRunNone($ctx);
		}

		return $matches;
	}

	/**
	 * @param array $check
	 * @return bool
	 */
	public function check(array $check) {
		try {
			$checkInstance = $this->container->query($check['class']);
		} catch (QueryException $e) {
			// Check does not exist, assume it matches.
			return true;
		}

		if ($checkInstance instanceof IFileCheck) {
			if (empty($this->fileInfo)) {
				throw new RuntimeException('Must set file info before running the check');
			}
			$checkInstance->setFileInfo($this->fileInfo['storage'], $this->fileInfo['path'], $this->fileInfo['isDir']);
		} elseif ($checkInstance instanceof IEntityCheck) {
			foreach ($this->contexts as $entityInfo) {
				[$entity, $subject] = $entityInfo;
				$checkInstance->setEntitySubject($entity, $subject);
			}
		} elseif (!$checkInstance instanceof ICheck) {
			// Check is invalid
			throw new \UnexpectedValueException($this->l->t('Check %s is invalid or does not exist', $check['class']));
		}
		return $checkInstance->executeCheck($check['operator'], $check['value']);
	}
}

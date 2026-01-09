<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine;

use OCA\WorkflowEngine\Check\FileMimeType;
use OCA\WorkflowEngine\Check\FileName;
use OCA\WorkflowEngine\Check\FileSize;
use OCA\WorkflowEngine\Check\FileSystemTags;
use OCA\WorkflowEngine\Check\RequestRemoteAddress;
use OCA\WorkflowEngine\Check\RequestTime;
use OCA\WorkflowEngine\Check\RequestURL;
use OCA\WorkflowEngine\Check\RequestUserAgent;
use OCA\WorkflowEngine\Check\UserGroupMembership;
use OCA\WorkflowEngine\Entity\File;
use OCA\WorkflowEngine\Helper\ScopeContext;
use OCA\WorkflowEngine\Service\Logger;
use OCA\WorkflowEngine\Service\RuleMatcher;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Cache\CappedMemoryCache;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\WorkflowEngine\Events\RegisterChecksEvent;
use OCP\WorkflowEngine\Events\RegisterEntitiesEvent;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IComplexOperation;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IEntityEvent;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IOperation;
use OCP\WorkflowEngine\IRuleMatcher;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-type Check = array{id: int, class: class-string<ICheck>, operator: string, value: string, hash: string}
 */
class Manager implements IManager {
	/** @var array[] */
	protected array $operations = [];

	/** @var array<int, Check> */
	protected array $checks = [];

	/** @var IEntity[] */
	protected array $registeredEntities = [];

	/** @var IOperation[] */
	protected array $registeredOperators = [];

	/** @var ICheck[] */
	protected array $registeredChecks = [];

	/** @var CappedMemoryCache<int[]> */
	protected CappedMemoryCache $operationsByScope;

	public function __construct(
		protected readonly IDBConnection $connection,
		protected readonly ContainerInterface $container,
		protected readonly IL10N $l,
		protected readonly LoggerInterface $logger,
		protected readonly IUserSession $session,
		private readonly IEventDispatcher $dispatcher,
		private readonly IAppConfig $appConfig,
		private readonly ICacheFactory $cacheFactory,
	) {
		$this->operationsByScope = new CappedMemoryCache(64);
	}

	public function getRuleMatcher(): IRuleMatcher {
		return new RuleMatcher(
			$this->session,
			$this->container,
			$this->l,
			$this,
			$this->container->get(Logger::class)
		);
	}

	public function getAllConfiguredEvents() {
		$cache = $this->cacheFactory->createDistributed('flow');
		$cached = $cache->get('events');
		if ($cached !== null) {
			return $cached;
		}

		$query = $this->connection->getQueryBuilder();

		$query->select('class', 'entity')
			->selectAlias($query->expr()->castColumn('events', IQueryBuilder::PARAM_STR), 'events')
			->from('flow_operations')
			->where($query->expr()->neq('events', $query->createNamedParameter('[]'), IQueryBuilder::PARAM_STR))
			->groupBy('class', 'entity', $query->expr()->castColumn('events', IQueryBuilder::PARAM_STR));

		$result = $query->executeQuery();
		$operations = [];
		while ($row = $result->fetchAssociative()) {
			$eventNames = \json_decode($row['events']);

			$operation = $row['class'];
			$entity = $row['entity'];

			$operations[$operation] = $operations[$row['class']] ?? [];
			$operations[$operation][$entity] = $operations[$operation][$entity] ?? [];

			$operations[$operation][$entity] = array_unique(array_merge($operations[$operation][$entity], $eventNames ?? []));
		}
		$result->closeCursor();

		$cache->set('events', $operations, 3600);

		return $operations;
	}

	/**
	 * @param class-string<IOperation> $operationClass
	 * @return ScopeContext[]
	 */
	public function getAllConfiguredScopesForOperation(string $operationClass): array {
		/** @var array<class-string<IOperation>, ScopeContext[]> $scopesByOperation */
		static $scopesByOperation = [];
		if (isset($scopesByOperation[$operationClass])) {
			return $scopesByOperation[$operationClass];
		}

		try {
			/** @var IOperation $operation */
			$operation = $this->container->get($operationClass);
		} catch (ContainerExceptionInterface $e) {
			return [];
		}

		$query = $this->connection->getQueryBuilder();

		$query->selectDistinct('s.type')
			->addSelect('s.value')
			->from('flow_operations', 'o')
			->leftJoin('o', 'flow_operations_scope', 's', $query->expr()->eq('o.id', 's.operation_id'))
			->where($query->expr()->eq('o.class', $query->createParameter('operationClass')));

		$query->setParameters(['operationClass' => $operationClass]);
		$result = $query->executeQuery();

		$scopesByOperation[$operationClass] = [];
		while ($row = $result->fetchAssociative()) {
			$scope = new ScopeContext($row['type'], $row['value']);

			if (!$operation->isAvailableForScope((int)$row['type'])) {
				continue;
			}

			$scopesByOperation[$operationClass][$scope->getHash()] = $scope;
		}

		return $scopesByOperation[$operationClass];
	}

	public function getAllOperations(ScopeContext $scopeContext): array {
		if (isset($this->operations[$scopeContext->getHash()])) {
			return $this->operations[$scopeContext->getHash()];
		}

		$query = $this->connection->getQueryBuilder();

		$query->select('o.*')
			->selectAlias('s.type', 'scope_type')
			->selectAlias('s.value', 'scope_actor_id')
			->from('flow_operations', 'o')
			->leftJoin('o', 'flow_operations_scope', 's', $query->expr()->eq('o.id', 's.operation_id'))
			->where($query->expr()->eq('s.type', $query->createParameter('scope')));

		if ($scopeContext->getScope() === IManager::SCOPE_USER) {
			$query->andWhere($query->expr()->eq('s.value', $query->createParameter('scopeId')));
		}

		$query->setParameters(['scope' => $scopeContext->getScope(), 'scopeId' => $scopeContext->getScopeId()]);
		$result = $query->executeQuery();

		$this->operations[$scopeContext->getHash()] = [];
		while ($row = $result->fetchAssociative()) {
			try {
				/** @var IOperation $operation */
				$operation = $this->container->get($row['class']);
			} catch (ContainerExceptionInterface $e) {
				continue;
			}

			if (!$operation->isAvailableForScope((int)$row['scope_type'])) {
				continue;
			}

			if (!isset($this->operations[$scopeContext->getHash()][$row['class']])) {
				$this->operations[$scopeContext->getHash()][$row['class']] = [];
			}
			$this->operations[$scopeContext->getHash()][$row['class']][] = $row;
		}

		return $this->operations[$scopeContext->getHash()];
	}

	public function getOperations(string $class, ScopeContext $scopeContext): array {
		if (!isset($this->operations[$scopeContext->getHash()])) {
			$this->getAllOperations($scopeContext);
		}
		return $this->operations[$scopeContext->getHash()][$class] ?? [];
	}

	/**
	 * @param int $id
	 * @return array
	 * @throws \UnexpectedValueException
	 */
	protected function getOperation($id) {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('flow_operations')
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		$result = $query->executeQuery();
		$row = $result->fetchAssociative();
		$result->closeCursor();

		if ($row) {
			return $row;
		}

		throw new \UnexpectedValueException($this->l->t('Operation #%s does not exist', [$id]));
	}

	protected function insertOperation(
		string $class,
		string $name,
		array $checkIds,
		string $operation,
		string $entity,
		array $events,
	): int {
		$query = $this->connection->getQueryBuilder();
		$query->insert('flow_operations')
			->values([
				'class' => $query->createNamedParameter($class),
				'name' => $query->createNamedParameter($name),
				'checks' => $query->createNamedParameter(json_encode(array_unique($checkIds))),
				'operation' => $query->createNamedParameter($operation),
				'entity' => $query->createNamedParameter($entity),
				'events' => $query->createNamedParameter(json_encode($events))
			]);
		$query->executeStatement();

		$this->cacheFactory->createDistributed('flow')->remove('events');

		return $query->getLastInsertId();
	}

	/**
	 * @param string $class
	 * @param string $name
	 * @param list<Check> $checks
	 * @param string $operation
	 * @return array The added operation
	 * @throws \UnexpectedValueException
	 * @throws Exception
	 */
	public function addOperation(
		string $class,
		string $name,
		array $checks,
		string $operation,
		ScopeContext $scope,
		string $entity,
		array $events,
	) {
		$this->validateOperation($class, $name, $checks, $operation, $scope, $entity, $events);

		$this->connection->beginTransaction();

		try {
			$checkIds = [];
			foreach ($checks as $check) {
				$checkIds[] = $this->addCheck($check['class'], $check['operator'], $check['value']);
			}

			$id = $this->insertOperation($class, $name, $checkIds, $operation, $entity, $events);
			$this->addScope($id, $scope);

			$this->connection->commit();
		} catch (Exception $e) {
			$this->connection->rollBack();
			throw $e;
		}

		return $this->getOperation($id);
	}

	protected function canModify(int $id, ScopeContext $scopeContext):bool {
		if (isset($this->operationsByScope[$scopeContext->getHash()])) {
			return in_array($id, $this->operationsByScope[$scopeContext->getHash()], true);
		}

		$qb = $this->connection->getQueryBuilder();
		$qb = $qb->select('o.id')
			->from('flow_operations', 'o')
			->leftJoin('o', 'flow_operations_scope', 's', $qb->expr()->eq('o.id', 's.operation_id'))
			->where($qb->expr()->eq('s.type', $qb->createParameter('scope')));

		if ($scopeContext->getScope() !== IManager::SCOPE_ADMIN) {
			$qb->andWhere($qb->expr()->eq('s.value', $qb->createParameter('scopeId')));
		}

		$qb->setParameters(['scope' => $scopeContext->getScope(), 'scopeId' => $scopeContext->getScopeId()]);
		$result = $qb->executeQuery();

		$operations = [];
		while (($opId = $result->fetchOne()) !== false) {
			$operations[] = (int)$opId;
		}
		$this->operationsByScope[$scopeContext->getHash()] = $operations;
		$result->closeCursor();

		return in_array($id, $this->operationsByScope[$scopeContext->getHash()], true);
	}

	/**
	 * @param int $id
	 * @param string $name
	 * @param array[] $checks
	 * @param string $operation
	 * @return array The updated operation
	 * @throws \UnexpectedValueException
	 * @throws \DomainException
	 * @throws Exception
	 */
	public function updateOperation(
		int $id,
		string $name,
		array $checks,
		string $operation,
		ScopeContext $scopeContext,
		string $entity,
		array $events,
	): array {
		if (!$this->canModify($id, $scopeContext)) {
			throw new \DomainException('Target operation not within scope');
		};
		$row = $this->getOperation($id);
		$this->validateOperation($row['class'], $name, $checks, $operation, $scopeContext, $entity, $events);

		$checkIds = [];
		try {
			$this->connection->beginTransaction();
			foreach ($checks as $check) {
				$checkIds[] = $this->addCheck($check['class'], $check['operator'], $check['value']);
			}

			$query = $this->connection->getQueryBuilder();
			$query->update('flow_operations')
				->set('name', $query->createNamedParameter($name))
				->set('checks', $query->createNamedParameter(json_encode(array_unique($checkIds))))
				->set('operation', $query->createNamedParameter($operation))
				->set('entity', $query->createNamedParameter($entity))
				->set('events', $query->createNamedParameter(json_encode($events)))
				->where($query->expr()->eq('id', $query->createNamedParameter($id)));
			$query->executeStatement();
			$this->connection->commit();
		} catch (Exception $e) {
			$this->connection->rollBack();
			throw $e;
		}
		unset($this->operations[$scopeContext->getHash()]);
		$this->cacheFactory->createDistributed('flow')->remove('events');

		return $this->getOperation($id);
	}

	/**
	 * @throws \UnexpectedValueException
	 * @throws Exception
	 * @throws \DomainException
	 */
	public function deleteOperation(int $id, ScopeContext $scopeContext): bool {
		if (!$this->canModify($id, $scopeContext)) {
			throw new \DomainException('Target operation not within scope');
		};
		$query = $this->connection->getQueryBuilder();
		try {
			$this->connection->beginTransaction();
			$result = (bool)$query->delete('flow_operations')
				->where($query->expr()->eq('id', $query->createNamedParameter($id)))
				->executeStatement();
			if ($result) {
				$qb = $this->connection->getQueryBuilder();
				$result = (bool)$qb->delete('flow_operations_scope')
					->where($qb->expr()->eq('operation_id', $qb->createNamedParameter($id)))
					->executeStatement();
			}
			$this->connection->commit();
		} catch (Exception $e) {
			$this->connection->rollBack();
			throw $e;
		}

		if (isset($this->operations[$scopeContext->getHash()])) {
			unset($this->operations[$scopeContext->getHash()]);
		}

		$this->cacheFactory->createDistributed('flow')->remove('events');

		return $result;
	}

	/**
	 * @param class-string<IEntity> $entity
	 * @param array $events
	 */
	protected function validateEvents(string $entity, array $events, IOperation $operation): void {
		/** @psalm-suppress TaintedCallable newInstance is not called */
		$reflection = new \ReflectionClass($entity);
		if ($entity !== IEntity::class && !in_array(IEntity::class, $reflection->getInterfaceNames())) {
			throw new \UnexpectedValueException($this->l->t('Entity %s is invalid', [$entity]));
		}

		try {
			$instance = $this->container->get($entity);
		} catch (ContainerExceptionInterface $e) {
			throw new \UnexpectedValueException($this->l->t('Entity %s does not exist', [$entity]));
		}

		if (empty($events)) {
			if (!$operation instanceof IComplexOperation) {
				throw new \UnexpectedValueException($this->l->t('No events are chosen.'));
			}
			return;
		}

		$availableEvents = [];
		foreach ($instance->getEvents() as $event) {
			/** @var IEntityEvent $event */
			$availableEvents[] = $event->getEventName();
		}

		$diff = array_diff($events, $availableEvents);
		if (!empty($diff)) {
			throw new \UnexpectedValueException($this->l->t('Entity %s has no event %s', [$entity, array_shift($diff)]));
		}
	}

	/**
	 * @param class-string<IOperation> $class
	 * @param list<Check> $checks
	 * @param array $events
	 * @throws \UnexpectedValueException
	 */
	public function validateOperation(string $class, string $name, array $checks, string $operation, ScopeContext $scope, string $entity, array $events): void {
		if (strlen($operation) > IManager::MAX_OPERATION_VALUE_BYTES) {
			throw new \UnexpectedValueException($this->l->t('The provided operation data is too long'));
		}

		/** @psalm-suppress TaintedCallable newInstance is not called */
		$reflection = new \ReflectionClass($class);
		if ($class !== IOperation::class && !in_array(IOperation::class, $reflection->getInterfaceNames())) {
			throw new \UnexpectedValueException($this->l->t('Operation %s is invalid', [$class]) . join(', ', $reflection->getInterfaceNames()));
		}

		try {
			/** @var IOperation $instance */
			$instance = $this->container->get($class);
		} catch (ContainerExceptionInterface $e) {
			throw new \UnexpectedValueException($this->l->t('Operation %s does not exist', [$class]));
		}

		if (!$instance->isAvailableForScope($scope->getScope())) {
			throw new \UnexpectedValueException($this->l->t('Operation %s is invalid', [$class]));
		}

		$this->validateEvents($entity, $events, $instance);

		if (count($checks) === 0) {
			throw new \UnexpectedValueException($this->l->t('At least one check needs to be provided'));
		}

		$instance->validateOperation($name, $checks, $operation);

		foreach ($checks as $check) {
			if (!is_string($check['class'])) {
				throw new \UnexpectedValueException($this->l->t('Invalid check provided'));
			}

			if (strlen((string)$check['value']) > IManager::MAX_CHECK_VALUE_BYTES) {
				throw new \UnexpectedValueException($this->l->t('The provided check value is too long'));
			}

			$reflection = new \ReflectionClass($check['class']);
			if ($check['class'] !== ICheck::class && !in_array(ICheck::class, $reflection->getInterfaceNames())) {
				throw new \UnexpectedValueException($this->l->t('Check %s is invalid', [$class]));
			}

			try {
				/** @var ICheck $instance */
				$instance = $this->container->get($check['class']);
			} catch (ContainerExceptionInterface) {
				throw new \UnexpectedValueException($this->l->t('Check %s does not exist', [$class]));
			}

			if (!empty($instance->supportedEntities())
				&& !in_array($entity, $instance->supportedEntities())
			) {
				throw new \UnexpectedValueException($this->l->t('Check %s is not allowed with this entity', [$class]));
			}

			$instance->validateCheck($check['operator'], $check['value']);
		}
	}

	/**
	 * @param int[] $checkIds
	 * @return array<int, Check>
	 */
	public function getChecks(array $checkIds): array {
		$checkIds = array_map('intval', $checkIds);

		$checks = [];
		foreach ($checkIds as $i => $checkId) {
			if (isset($this->checks[$checkId])) {
				$checks[$checkId] = $this->checks[$checkId];
				unset($checkIds[$i]);
			}
		}

		if (empty($checkIds)) {
			return $checks;
		}

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('flow_checks')
			->where($query->expr()->in('id', $query->createNamedParameter($checkIds, IQueryBuilder::PARAM_INT_ARRAY)));
		$result = $query->executeQuery();

		while ($row = $result->fetchAssociative()) {
			/** @var Check $row */
			$this->checks[(int)$row['id']] = $row;
			$checks[(int)$row['id']] = $row;
		}
		$result->closeCursor();

		$checkIds = array_diff($checkIds, array_keys($checks));

		if (!empty($checkIds)) {
			$missingCheck = array_pop($checkIds);
			throw new \UnexpectedValueException($this->l->t('Check #%s does not exist', (string)$missingCheck));
		}

		return $checks;
	}

	/**
	 * @return int Check unique ID
	 */
	protected function addCheck(string $class, string $operator, string $value): int {
		$hash = md5($class . '::' . $operator . '::' . $value);

		$query = $this->connection->getQueryBuilder();
		$query->select('id')
			->from('flow_checks')
			->where($query->expr()->eq('hash', $query->createNamedParameter($hash)));
		$result = $query->executeQuery();

		if ($row = $result->fetchAssociative()) {
			$result->closeCursor();
			return (int)$row['id'];
		}

		$query = $this->connection->getQueryBuilder();
		$query->insert('flow_checks')
			->values([
				'class' => $query->createNamedParameter($class),
				'operator' => $query->createNamedParameter($operator),
				'value' => $query->createNamedParameter($value),
				'hash' => $query->createNamedParameter($hash),
			]);
		$query->executeStatement();

		return $query->getLastInsertId();
	}

	protected function addScope(int $operationId, ScopeContext $scope): void {
		$query = $this->connection->getQueryBuilder();

		$insertQuery = $query->insert('flow_operations_scope');
		$insertQuery->values([
			'operation_id' => $query->createNamedParameter($operationId),
			'type' => $query->createNamedParameter($scope->getScope()),
			'value' => $query->createNamedParameter($scope->getScopeId()),
		]);
		$insertQuery->executeStatement();
	}

	public function formatOperation(array $operation): array {
		$checkIds = json_decode($operation['checks'], true);
		$checks = $this->getChecks($checkIds);

		$operation['checks'] = [];
		foreach ($checks as $check) {
			// Remove internal values
			unset($check['id']);
			unset($check['hash']);

			$operation['checks'][] = $check;
		}
		$operation['events'] = json_decode($operation['events'], true) ?? [];


		return $operation;
	}

	/**
	 * @return IEntity[]
	 */
	public function getEntitiesList(): array {
		$this->dispatcher->dispatchTyped(new RegisterEntitiesEvent($this));

		return array_values(array_merge($this->getBuildInEntities(), $this->registeredEntities));
	}

	/**
	 * @return IOperation[]
	 */
	public function getOperatorList(): array {
		$this->dispatcher->dispatchTyped(new RegisterOperationsEvent($this));

		return array_merge($this->getBuildInOperators(), $this->registeredOperators);
	}

	/**
	 * @return ICheck[]
	 */
	public function getCheckList(): array {
		$this->dispatcher->dispatchTyped(new RegisterChecksEvent($this));

		return array_merge($this->getBuildInChecks(), $this->registeredChecks);
	}

	public function registerEntity(IEntity $entity): void {
		$this->registeredEntities[get_class($entity)] = $entity;
	}

	public function registerOperation(IOperation $operator): void {
		$this->registeredOperators[get_class($operator)] = $operator;
	}

	public function registerCheck(ICheck $check): void {
		$this->registeredChecks[get_class($check)] = $check;
	}

	/**
	 * @return IEntity[]
	 */
	protected function getBuildInEntities(): array {
		try {
			return [
				File::class => $this->container->get(File::class),
			];
		} catch (ContainerExceptionInterface $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return [];
		}
	}

	/**
	 * @return IOperation[]
	 */
	protected function getBuildInOperators(): array {
		try {
			return [
				// None yet
			];
		} catch (ContainerExceptionInterface $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return [];
		}
	}

	/**
	 * @return ICheck[]
	 */
	protected function getBuildInChecks(): array {
		try {
			return [
				$this->container->get(FileMimeType::class),
				$this->container->get(FileName::class),
				$this->container->get(FileSize::class),
				$this->container->get(FileSystemTags::class),
				$this->container->get(RequestRemoteAddress::class),
				$this->container->get(RequestTime::class),
				$this->container->get(RequestURL::class),
				$this->container->get(RequestUserAgent::class),
				$this->container->get(UserGroupMembership::class),
			];
		} catch (ContainerExceptionInterface $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return [];
		}
	}

	public function isUserScopeEnabled(): bool {
		return !$this->appConfig->getAppValueBool('user_scope_disabled');
	}
}

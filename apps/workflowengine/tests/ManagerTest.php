<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Tests;

use OC\Files\Config\UserMountCache;
use OCA\WorkflowEngine\Entity\File;
use OCA\WorkflowEngine\Helper\ScopeContext;
use OCA\WorkflowEngine\Manager;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\SystemTag\ISystemTagManager;
use OCP\WorkflowEngine\Events\RegisterEntitiesEvent;
use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IEntityEvent;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IOperation;
use OCP\WorkflowEngine\IRuleMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class TestAdminOp implements IOperation {
	public function getDisplayName(): string {
		return 'Admin';
	}

	public function getDescription(): string {
		return '';
	}

	public function getIcon(): string {
		return '';
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}

	public function validateOperation(string $name, array $checks, string $operation): void {
	}

	public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void {
	}
}

class TestUserOp extends TestAdminOp {
	public function getDisplayName(): string {
		return 'User';
	}
}

/**
 * Class ManagerTest
 *
 * @package OCA\WorkflowEngine\Tests
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ManagerTest extends TestCase {
	protected Manager $manager;
	protected IDBConnection $db;
	protected LoggerInterface&MockObject $logger;
	protected ContainerInterface&MockObject $container;
	protected IUserSession&MockObject $session;
	protected IL10N&MockObject $l;
	protected IEventDispatcher&MockObject $dispatcher;
	protected IAppConfig&MockObject $config;
	protected ICacheFactory&MockObject $cacheFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->session = $this->createMock(IUserSession::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->config = $this->createMock(IAppConfig::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);

		$this->manager = new Manager(
			$this->db,
			$this->container,
			$this->l,
			$this->logger,
			$this->session,
			$this->dispatcher,
			$this->config,
			$this->cacheFactory
		);
		$this->clearTables();
	}

	protected function tearDown(): void {
		$this->clearTables();
		parent::tearDown();
	}

	protected function buildScope(?string $scopeId = null): MockObject&ScopeContext {
		$scopeContext = $this->createMock(ScopeContext::class);
		$scopeContext->expects($this->any())
			->method('getScope')
			->willReturn($scopeId ? IManager::SCOPE_USER : IManager::SCOPE_ADMIN);
		$scopeContext->expects($this->any())
			->method('getScopeId')
			->willReturn($scopeId ?? '');
		$scopeContext->expects($this->any())
			->method('getHash')
			->willReturn(md5($scopeId ?? ''));

		return $scopeContext;
	}

	public function clearTables() {
		$query = $this->db->getQueryBuilder();
		foreach (['flow_checks', 'flow_operations', 'flow_operations_scope'] as $table) {
			$query->delete($table)
				->executeStatement();
		}
	}

	public function testChecks(): void {
		$check1 = $this->invokePrivate($this->manager, 'addCheck', ['Test', 'equal', 1]);
		$check2 = $this->invokePrivate($this->manager, 'addCheck', ['Test', '!equal', 2]);

		$data = $this->manager->getChecks([$check1]);
		$this->assertArrayHasKey($check1, $data);
		$this->assertArrayNotHasKey($check2, $data);

		$data = $this->manager->getChecks([$check1, $check2]);
		$this->assertArrayHasKey($check1, $data);
		$this->assertArrayHasKey($check2, $data);

		$data = $this->manager->getChecks([$check2, $check1]);
		$this->assertArrayHasKey($check1, $data);
		$this->assertArrayHasKey($check2, $data);

		$data = $this->manager->getChecks([$check2]);
		$this->assertArrayNotHasKey($check1, $data);
		$this->assertArrayHasKey($check2, $data);
	}

	public function testScope(): void {
		$adminScope = $this->buildScope();
		$userScope = $this->buildScope('jackie');
		$entity = File::class;

		$opId1 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestOp', 'Test01', [11, 22], 'foo', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId1, $adminScope]);

		$opId2 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestOp', 'Test02', [33, 22], 'bar', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId2, $userScope]);
		$opId3 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestOp', 'Test03', [11, 44], 'foobar', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId3, $userScope]);

		$this->assertTrue($this->invokePrivate($this->manager, 'canModify', [$opId1, $adminScope]));
		$this->assertFalse($this->invokePrivate($this->manager, 'canModify', [$opId2, $adminScope]));
		$this->assertFalse($this->invokePrivate($this->manager, 'canModify', [$opId3, $adminScope]));

		$this->assertFalse($this->invokePrivate($this->manager, 'canModify', [$opId1, $userScope]));
		$this->assertTrue($this->invokePrivate($this->manager, 'canModify', [$opId2, $userScope]));
		$this->assertTrue($this->invokePrivate($this->manager, 'canModify', [$opId3, $userScope]));
	}

	public function testGetAllOperations(): void {
		$adminScope = $this->buildScope();
		$userScope = $this->buildScope('jackie');
		$entity = File::class;

		$adminOperation = $this->createMock(IOperation::class);
		$adminOperation->expects($this->any())
			->method('isAvailableForScope')
			->willReturnMap([
				[IManager::SCOPE_ADMIN, true],
				[IManager::SCOPE_USER, false],
			]);
		$userOperation = $this->createMock(IOperation::class);
		$userOperation->expects($this->any())
			->method('isAvailableForScope')
			->willReturnMap([
				[IManager::SCOPE_ADMIN, false],
				[IManager::SCOPE_USER, true],
			]);

		$this->container->expects($this->any())
			->method('get')
			->willReturnCallback(function ($className) use ($adminOperation, $userOperation) {
				switch ($className) {
					case 'OCA\WFE\TestAdminOp':
						return $adminOperation;
					case 'OCA\WFE\TestUserOp':
						return $userOperation;
				}
			});

		$opId1 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestAdminOp', 'Test01', [11, 22], 'foo', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId1, $adminScope]);

		$opId2 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestUserOp', 'Test02', [33, 22], 'bar', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId2, $userScope]);
		$opId3 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestUserOp', 'Test03', [11, 44], 'foobar', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId3, $userScope]);

		$opId4 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestAdminOp', 'Test04', [41, 10, 4], 'NoBar', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId4, $userScope]);

		$adminOps = $this->manager->getAllOperations($adminScope);
		$userOps = $this->manager->getAllOperations($userScope);

		$this->assertSame(1, count($adminOps));
		$this->assertTrue(array_key_exists('OCA\WFE\TestAdminOp', $adminOps));
		$this->assertFalse(array_key_exists('OCA\WFE\TestUserOp', $adminOps));

		$this->assertSame(1, count($userOps));
		$this->assertFalse(array_key_exists('OCA\WFE\TestAdminOp', $userOps));
		$this->assertTrue(array_key_exists('OCA\WFE\TestUserOp', $userOps));
		$this->assertSame(2, count($userOps['OCA\WFE\TestUserOp']));
	}

	public function testGetOperations(): void {
		$adminScope = $this->buildScope();
		$userScope = $this->buildScope('jackie');
		$entity = File::class;

		$opId1 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestOp', 'Test01', [11, 22], 'foo', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId1, $adminScope]);
		$opId4 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\OtherTestOp', 'Test04', [5], 'foo', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId4, $adminScope]);

		$opId2 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestOp', 'Test02', [33, 22], 'bar', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId2, $userScope]);
		$opId3 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestOp', 'Test03', [11, 44], 'foobar', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId3, $userScope]);
		$opId5 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\OtherTestOp', 'Test05', [5], 'foobar', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId5, $userScope]);

		$operation = $this->createMock(IOperation::class);
		$operation->expects($this->any())
			->method('isAvailableForScope')
			->willReturnMap([
				[IManager::SCOPE_ADMIN, true],
				[IManager::SCOPE_USER, true],
			]);

		$this->container->expects($this->any())
			->method('get')
			->willReturnCallback(function ($className) use ($operation) {
				switch ($className) {
					case 'OCA\WFE\TestOp':
						return $operation;
					case 'OCA\WFE\OtherTestOp':
						throw new QueryException();
				}
			});

		$adminOps = $this->manager->getOperations('OCA\WFE\TestOp', $adminScope);
		$userOps = $this->manager->getOperations('OCA\WFE\TestOp', $userScope);

		$this->assertSame(1, count($adminOps));
		array_walk($adminOps, function ($op): void {
			$this->assertTrue($op['class'] === 'OCA\WFE\TestOp');
		});

		$this->assertSame(2, count($userOps));
		array_walk($userOps, function ($op): void {
			$this->assertTrue($op['class'] === 'OCA\WFE\TestOp');
		});
	}

	public function testGetAllConfiguredEvents(): void {
		$adminScope = $this->buildScope();
		$userScope = $this->buildScope('jackie');
		$entity = File::class;

		$opId5 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\OtherTestOp', 'Test04', [], 'foo', $entity, [NodeCreatedEvent::class]]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId5, $userScope]);

		$allOperations = null;

		$cache = $this->createMock(ICache::class);
		$cache
			->method('get')
			->willReturnCallback(function () use (&$allOperations) {
				if ($allOperations) {
					return $allOperations;
				}

				return null;
			});

		$this->cacheFactory->method('createDistributed')->willReturn($cache);
		$allOperations = $this->manager->getAllConfiguredEvents();
		$this->assertCount(1, $allOperations);

		$allOperationsCached = $this->manager->getAllConfiguredEvents();
		$this->assertCount(1, $allOperationsCached);
		$this->assertEquals($allOperationsCached, $allOperations);
	}

	public function testUpdateOperation(): void {
		$adminScope = $this->buildScope();
		$userScope = $this->buildScope('jackie');
		$entity = File::class;

		$cache = $this->createMock(ICache::class);
		$cache->expects($this->exactly(4))
			->method('remove')
			->with('events');
		$this->cacheFactory->method('createDistributed')
			->willReturn($cache);

		$expectedCalls = [
			[IManager::SCOPE_ADMIN],
			[IManager::SCOPE_USER],
		];
		$i = 0;
		$operationMock = $this->createMock(IOperation::class);
		$operationMock->expects($this->any())
			->method('isAvailableForScope')
			->willReturnCallback(function () use (&$expectedCalls, &$i): bool {
				$this->assertLessThanOrEqual(1, $i);
				$this->assertEquals($expectedCalls[$i], func_get_args());
				$i++;
				return true;
			});

		$this->container->expects($this->any())
			->method('get')
			->willReturnCallback(function ($class) use ($operationMock) {
				if (substr($class, -2) === 'Op') {
					return $operationMock;
				} elseif ($class === File::class) {
					return $this->getMockBuilder(File::class)
						->setConstructorArgs([
							$this->l,
							$this->createMock(IURLGenerator::class),
							$this->createMock(IRootFolder::class),
							$this->createMock(IUserSession::class),
							$this->createMock(ISystemTagManager::class),
							$this->createMock(IUserManager::class),
							$this->createMock(UserMountCache::class),
							$this->createMock(IMountManager::class),
						])
						->onlyMethods($this->filterClassMethods(File::class, ['getEvents']))
						->getMock();
				}
				return $this->createMock(ICheck::class);
			});

		$opId1 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			[TestAdminOp::class, 'Test01', [11, 22], 'foo', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId1, $adminScope]);

		$opId2 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			[TestUserOp::class, 'Test02', [33, 22], 'bar', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId2, $userScope]);

		$check1 = ['class' => ICheck::class, 'operator' => 'eq', 'value' => 'asdf'];
		$check2 = ['class' => ICheck::class, 'operator' => 'eq', 'value' => 23456];

		/** @noinspection PhpUnhandledExceptionInspection */
		$op = $this->manager->updateOperation($opId1, 'Test01a', [$check1, $check2], 'foohur', $adminScope, $entity, ['\OCP\Files::postDelete']);
		$this->assertSame('Test01a', $op['name']);
		$this->assertSame('foohur', $op['operation']);

		/** @noinspection PhpUnhandledExceptionInspection */
		$op = $this->manager->updateOperation($opId2, 'Test02a', [$check1], 'barfoo', $userScope, $entity, ['\OCP\Files::postDelete']);
		$this->assertSame('Test02a', $op['name']);
		$this->assertSame('barfoo', $op['operation']);

		foreach ([[$adminScope, $opId2], [$userScope, $opId1]] as $run) {
			try {
				/** @noinspection PhpUnhandledExceptionInspection */
				$this->manager->updateOperation($run[1], 'Evil', [$check2], 'hackx0r', $run[0], $entity, []);
				$this->assertTrue(false, 'DomainException not thrown');
			} catch (\DomainException $e) {
				$this->assertTrue(true);
			}
		}
	}

	public function testDeleteOperation(): void {
		$adminScope = $this->buildScope();
		$userScope = $this->buildScope('jackie');
		$entity = File::class;

		$cache = $this->createMock(ICache::class);
		$cache->expects($this->exactly(4))
			->method('remove')
			->with('events');
		$this->cacheFactory->method('createDistributed')->willReturn($cache);

		$opId1 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestAdminOp', 'Test01', [11, 22], 'foo', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId1, $adminScope]);

		$opId2 = $this->invokePrivate(
			$this->manager,
			'insertOperation',
			['OCA\WFE\TestUserOp', 'Test02', [33, 22], 'bar', $entity, []]
		);
		$this->invokePrivate($this->manager, 'addScope', [$opId2, $userScope]);

		foreach ([[$adminScope, $opId2], [$userScope, $opId1]] as $run) {
			try {
				/** @noinspection PhpUnhandledExceptionInspection */
				$this->manager->deleteOperation($run[1], $run[0]);
				$this->assertTrue(false, 'DomainException not thrown');
			} catch (\Exception $e) {
				$this->assertInstanceOf(\DomainException::class, $e);
			}
		}

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->manager->deleteOperation($opId1, $adminScope);
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->manager->deleteOperation($opId2, $userScope);

		foreach ([$opId1, $opId2] as $opId) {
			try {
				$this->invokePrivate($this->manager, 'getOperation', [$opId]);
				$this->assertTrue(false, 'UnexpectedValueException not thrown');
			} catch (\Exception $e) {
				$this->assertInstanceOf(\UnexpectedValueException::class, $e);
			}
		}
	}

	public function testGetEntitiesListBuildInOnly(): void {
		$fileEntityMock = $this->createMock(File::class);

		$this->container->expects($this->once())
			->method('get')
			->with(File::class)
			->willReturn($fileEntityMock);

		$entities = $this->manager->getEntitiesList();

		$this->assertCount(1, $entities);
		$this->assertInstanceOf(IEntity::class, $entities[0]);
	}

	public function testGetEntitiesList(): void {
		$fileEntityMock = $this->createMock(File::class);

		$this->container->expects($this->once())
			->method('get')
			->with(File::class)
			->willReturn($fileEntityMock);

		$extraEntity = $this->createMock(IEntity::class);

		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function (RegisterEntitiesEvent $e) use ($extraEntity): void {
				$this->manager->registerEntity($extraEntity);
			});

		$entities = $this->manager->getEntitiesList();

		$this->assertCount(2, $entities);

		$entityTypeCounts = array_reduce($entities, function (array $carry, IEntity $entity) {
			if ($entity instanceof File) {
				$carry[0]++;
			} elseif ($entity instanceof IEntity) {
				$carry[1]++;
			}
			return $carry;
		}, [0, 0]);

		$this->assertSame(1, $entityTypeCounts[0]);
		$this->assertSame(1, $entityTypeCounts[1]);
	}

	public function testValidateOperationOK(): void {
		$check = [
			'id' => 1,
			'class' => ICheck::class,
			'operator' => 'is',
			'value' => 'barfoo',
			'hash' => 'abc',
		];

		$operationMock = $this->createMock(IOperation::class);
		$entityMock = $this->createMock(IEntity::class);
		$eventEntityMock = $this->createMock(IEntityEvent::class);
		$checkMock = $this->createMock(ICheck::class);
		$scopeMock = $this->createMock(ScopeContext::class);

		$scopeMock->expects($this->any())
			->method('getScope')
			->willReturn(IManager::SCOPE_ADMIN);

		$operationMock->expects($this->once())
			->method('isAvailableForScope')
			->with(IManager::SCOPE_ADMIN)
			->willReturn(true);

		$operationMock->expects($this->once())
			->method('validateOperation')
			->with('test', [$check], 'operationData');

		$entityMock->expects($this->any())
			->method('getEvents')
			->willReturn([$eventEntityMock]);

		$eventEntityMock->expects($this->any())
			->method('getEventName')
			->willReturn('MyEvent');

		$checkMock->expects($this->any())
			->method('supportedEntities')
			->willReturn([IEntity::class]);
		$checkMock->expects($this->atLeastOnce())
			->method('validateCheck');

		$this->container->expects($this->any())
			->method('get')
			->willReturnCallback(function ($className) use ($operationMock, $entityMock, $eventEntityMock, $checkMock) {
				switch ($className) {
					case IOperation::class:
						return $operationMock;
					case IEntity::class:
						return $entityMock;
					case IEntityEvent::class:
						return $eventEntityMock;
					case ICheck::class:
						return $checkMock;
					default:
						return $this->createMock($className);
				}
			});

		$this->manager->validateOperation(IOperation::class, 'test', [$check], 'operationData', $scopeMock, IEntity::class, ['MyEvent']);
	}

	public function testValidateOperationCheckInputLengthError(): void {
		$check = [
			'id' => 1,
			'class' => ICheck::class,
			'operator' => 'is',
			'value' => str_pad('', IManager::MAX_CHECK_VALUE_BYTES + 1, 'FooBar'),
			'hash' => 'abc',
		];

		$operationMock = $this->createMock(IOperation::class);
		$entityMock = $this->createMock(IEntity::class);
		$eventEntityMock = $this->createMock(IEntityEvent::class);
		$checkMock = $this->createMock(ICheck::class);
		$scopeMock = $this->createMock(ScopeContext::class);

		$scopeMock->expects($this->any())
			->method('getScope')
			->willReturn(IManager::SCOPE_ADMIN);

		$operationMock->expects($this->once())
			->method('isAvailableForScope')
			->with(IManager::SCOPE_ADMIN)
			->willReturn(true);

		$operationMock->expects($this->once())
			->method('validateOperation')
			->with('test', [$check], 'operationData');

		$entityMock->expects($this->any())
			->method('getEvents')
			->willReturn([$eventEntityMock]);

		$eventEntityMock->expects($this->any())
			->method('getEventName')
			->willReturn('MyEvent');

		$checkMock->expects($this->any())
			->method('supportedEntities')
			->willReturn([IEntity::class]);
		$checkMock->expects($this->never())
			->method('validateCheck');

		$this->container->expects($this->any())
			->method('get')
			->willReturnCallback(function ($className) use ($operationMock, $entityMock, $eventEntityMock, $checkMock) {
				return match ($className) {
					IOperation::class => $operationMock,
					IEntity::class => $entityMock,
					IEntityEvent::class => $eventEntityMock,
					ICheck::class => $checkMock,
					default => $this->createMock($className),
				};
			});

		try {
			$this->manager->validateOperation(IOperation::class, 'test', [$check], 'operationData', $scopeMock, IEntity::class, ['MyEvent']);
		} catch (\UnexpectedValueException $e) {
			$this->assertSame('The provided check value is too long', $e->getMessage());
		}
	}

	public function testValidateOperationDataLengthError(): void {
		$check = [
			'id' => 1,
			'class' => ICheck::class,
			'operator' => 'is',
			'value' => 'barfoo',
			'hash' => 'abc',
		];
		$operationData = str_pad('', IManager::MAX_OPERATION_VALUE_BYTES + 1, 'FooBar');

		$operationMock = $this->createMock(IOperation::class);
		$entityMock = $this->createMock(IEntity::class);
		$eventEntityMock = $this->createMock(IEntityEvent::class);
		$checkMock = $this->createMock(ICheck::class);
		$scopeMock = $this->createMock(ScopeContext::class);

		$scopeMock->expects($this->any())
			->method('getScope')
			->willReturn(IManager::SCOPE_ADMIN);

		$operationMock->expects($this->never())
			->method('validateOperation');

		$entityMock->expects($this->any())
			->method('getEvents')
			->willReturn([$eventEntityMock]);

		$eventEntityMock->expects($this->any())
			->method('getEventName')
			->willReturn('MyEvent');

		$checkMock->expects($this->any())
			->method('supportedEntities')
			->willReturn([IEntity::class]);
		$checkMock->expects($this->never())
			->method('validateCheck');

		$this->container->expects($this->any())
			->method('get')
			->willReturnCallback(function ($className) use ($operationMock, $entityMock, $eventEntityMock, $checkMock) {
				return match ($className) {
					IOperation::class => $operationMock,
					IEntity::class => $entityMock,
					IEntityEvent::class => $eventEntityMock,
					ICheck::class => $checkMock,
					default => $this->createMock($className),
				};
			});

		try {
			$this->manager->validateOperation(IOperation::class, 'test', [$check], $operationData, $scopeMock, IEntity::class, ['MyEvent']);
		} catch (\UnexpectedValueException $e) {
			$this->assertSame('The provided operation data is too long', $e->getMessage());
		}
	}

	public function testValidateOperationScopeNotAvailable(): void {
		$check = [
			'id' => 1,
			'class' => ICheck::class,
			'operator' => 'is',
			'value' => 'barfoo',
		];
		$operationData = str_pad('', IManager::MAX_OPERATION_VALUE_BYTES - 1, 'FooBar');

		$operationMock = $this->createMock(IOperation::class);
		$entityMock = $this->createMock(IEntity::class);
		$eventEntityMock = $this->createMock(IEntityEvent::class);
		$checkMock = $this->createMock(ICheck::class);
		$scopeMock = $this->createMock(ScopeContext::class);

		$scopeMock->expects($this->any())
			->method('getScope')
			->willReturn(IManager::SCOPE_ADMIN);

		$operationMock->expects($this->once())
			->method('isAvailableForScope')
			->with(IManager::SCOPE_ADMIN)
			->willReturn(false);

		$operationMock->expects($this->never())
			->method('validateOperation');

		$entityMock->expects($this->any())
			->method('getEvents')
			->willReturn([$eventEntityMock]);

		$eventEntityMock->expects($this->any())
			->method('getEventName')
			->willReturn('MyEvent');

		$checkMock->expects($this->any())
			->method('supportedEntities')
			->willReturn([IEntity::class]);
		$checkMock->expects($this->never())
			->method('validateCheck');

		$this->container->expects($this->any())
			->method('get')
			->willReturnCallback(function ($className) use ($operationMock, $entityMock, $eventEntityMock, $checkMock) {
				return match ($className) {
					IOperation::class => $operationMock,
					IEntity::class => $entityMock,
					IEntityEvent::class => $eventEntityMock,
					ICheck::class => $checkMock,
					default => $this->createMock($className),
				};
			});

		try {
			$this->manager->validateOperation(IOperation::class, 'test', [$check], $operationData, $scopeMock, IEntity::class, ['MyEvent']);
		} catch (\UnexpectedValueException $e) {
			$this->assertSame('Operation OCP\WorkflowEngine\IOperation is invalid', $e->getMessage());
		}
	}
}

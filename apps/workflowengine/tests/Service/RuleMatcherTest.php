<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Tests\Service;

use LogicException;
use NCU\WorkflowEngine\RuntimeOperation;
use OCA\WorkflowEngine\Manager;
use OCA\WorkflowEngine\Service\Logger;
use OCA\WorkflowEngine\Service\RuleMatcher;
use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IFileCheck;
use OCP\WorkflowEngine\IOperation;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Test\TestCase;
use UnexpectedValueException;

class RuleMatcherTest extends TestCase {
	private IUserSession&MockObject $session;
	private ContainerInterface&MockObject $container;
	private IL10N&MockObject $l;
	private Manager&MockObject $manager;
	private Logger&MockObject $logger;
	private RuleMatcher $ruleMatcher;
	private IOperation&MockObject $operation;
	private IEntity&MockObject $entity;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(IUserSession::class);
		$this->session->method('getUser')->willReturn(null);

		$this->container = $this->createMock(ContainerInterface::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->method('t')->willReturnArgument(0);

		$this->manager = $this->createMock(Manager::class);
		$this->manager->method('isUserScopeEnabled')->willReturn(false);
		$this->manager->method('getAllConfiguredScopesForOperation')->willReturn([]);
		$this->manager->method('getAllConfiguredScopesForRuntimeOperation')->willReturn([]);

		$this->logger = $this->createMock(Logger::class);

		$this->operation = $this->createMock(IOperation::class);
		$this->entity = $this->createMock(IEntity::class);

		$this->ruleMatcher = new RuleMatcher(
			$this->session,
			$this->container,
			$this->l,
			$this->manager,
			$this->logger,
		);
	}

	public function testSetOperationThrowsIfCalledTwice(): void {
		$this->ruleMatcher->setOperation($this->operation);
		$this->expectException(RuntimeException::class);
		$this->ruleMatcher->setOperation($this->operation);
	}

	public function testSetEntityThrowsIfCalledTwice(): void {
		$this->ruleMatcher->setEntity($this->entity);
		$this->expectException(RuntimeException::class);
		$this->ruleMatcher->setEntity($this->entity);
	}

	public function testSetEventNameThrowsIfCalledTwice(): void {
		$this->ruleMatcher->setEventName('MyEvent');
		$this->expectException(RuntimeException::class);
		$this->ruleMatcher->setEventName('MyEvent');
	}

	public function testGetEntityThrowsIfNotSet(): void {
		$this->expectException(LogicException::class);
		$this->ruleMatcher->getEntity();
	}

	public function testGetFlowsThrowsIfOperationNotSet(): void {
		$this->expectException(RuntimeException::class);
		$this->ruleMatcher->getFlows();
	}

	private function buildDbOperation(string $name = 'DbOp', array $events = ['MyEvent']): array {
		return [
			'id' => 1,
			'class' => get_class($this->operation),
			'name' => $name,
			'checks' => '[]',
			'operation' => '',
			'entity' => get_class($this->entity),
			'events' => json_encode($events),
		];
	}

	private function buildRuntimeOperation(string $name = 'RuntimeOp', array $events = ['MyEvent']): RuntimeOperation {
		return new RuntimeOperation(
			id: 'runtime-op-1',
			class: get_class($this->operation),
			name: $name,
			checks: [],
			operation: '',
			entity: get_class($this->entity),
			events: $events,
			appId: 'testapp',
		);
	}

	public function testGetFlowsReturnsMatchingDbOperation(): void {
		$this->manager->method('getOperations')->willReturn([$this->buildDbOperation()]);
		$this->manager->method('getRuntimeOperations')->willReturn([]);
		$this->manager->method('getChecks')->willReturn([]);

		$this->ruleMatcher->setOperation($this->operation);
		$this->ruleMatcher->setEventName('MyEvent');

		$result = $this->ruleMatcher->getFlows(true);

		$this->assertIsArray($result);
		$this->assertSame('DbOp', $result['name']);
	}

	public function testGetFlowsSkipsDbOperationWithNonMatchingEvent(): void {
		$this->manager->method('getOperations')->willReturn([$this->buildDbOperation('DbOp', ['OtherEvent'])]);
		$this->manager->method('getRuntimeOperations')->willReturn([]);
		$this->manager->method('getChecks')->willReturn([]);

		$this->ruleMatcher->setOperation($this->operation);
		$this->ruleMatcher->setEventName('MyEvent');

		$this->assertSame([], $this->ruleMatcher->getFlows(false));
	}

	public function testGetFlowsReturnsMatchingRuntimeOperation(): void {
		$this->manager->method('getOperations')->willReturn([]);
		$this->manager->method('getRuntimeOperations')->willReturn([$this->buildRuntimeOperation()]);
		$this->manager->method('getRuntimeChecks')->willReturn([]);

		$this->ruleMatcher->setOperation($this->operation);
		$this->ruleMatcher->setEventName('MyEvent');

		$result = $this->ruleMatcher->getFlows(true);

		$this->assertIsArray($result);
		$this->assertSame('RuntimeOp', $result['name']);
		$this->assertTrue($result['runtime']);
	}

	public function testGetFlowsSkipsRuntimeOperationWithNonMatchingEvent(): void {
		$this->manager->method('getOperations')->willReturn([]);
		$this->manager->method('getRuntimeOperations')->willReturn([$this->buildRuntimeOperation('RuntimeOp', ['OtherEvent'])]);
		$this->manager->method('getRuntimeChecks')->willReturn([]);

		$this->ruleMatcher->setOperation($this->operation);
		$this->ruleMatcher->setEventName('MyEvent');

		$this->assertSame([], $this->ruleMatcher->getFlows(false));
	}

	public function testGetFlowsMixedOperationsWithEventFilter(): void {
		$this->manager->method('getOperations')
			->willReturn([$this->buildDbOperation('DbOp', ['MyEvent'])]);
		$this->manager->method('getRuntimeOperations')
			->willReturn([$this->buildRuntimeOperation('RuntimeOp', ['OtherEvent'])]);
		$this->manager->method('getChecks')->willReturn([]);
		$this->manager->method('getRuntimeChecks')->willReturn([]);

		$this->ruleMatcher->setOperation($this->operation);
		$this->ruleMatcher->setEventName('MyEvent');

		$results = $this->ruleMatcher->getFlows(false);

		$this->assertCount(1, $results);
		$this->assertSame('DbOp', $results[0]['name']);
	}

	public function testGetFlowsReturnAllMatches(): void {
		$this->manager->method('getOperations')
			->willReturn([$this->buildDbOperation('DbOp')]);
		$this->manager->method('getRuntimeOperations')
			->willReturn([$this->buildRuntimeOperation('RuntimeOp')]);
		$this->manager->method('getChecks')->willReturn([]);
		$this->manager->method('getRuntimeChecks')->willReturn([]);

		$this->ruleMatcher->setOperation($this->operation);
		$this->ruleMatcher->setEventName('MyEvent');

		$results = $this->ruleMatcher->getFlows(false);

		$this->assertCount(2, $results);
		$names = array_column($results, 'name');
		$this->assertContains('DbOp', $names);
		$this->assertContains('RuntimeOp', $names);
	}

	public function testGetFlowsReturnFirstMatchOnly(): void {
		$this->manager->method('getOperations')
			->willReturn([$this->buildDbOperation('DbOp'), $this->buildDbOperation('DbOp2')]);
		$this->manager->method('getRuntimeOperations')->willReturn([]);
		$this->manager->method('getChecks')->willReturn([]);

		$this->ruleMatcher->setOperation($this->operation);
		$this->ruleMatcher->setEventName('MyEvent');

		$result = $this->ruleMatcher->getFlows(true);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('name', $result);
		$this->assertSame('DbOp', $result['name']);
	}

	public function testGetFlowsSkipsOperationWhenCheckFails(): void {
		$checkData = ['class' => ICheck::class, 'operator' => 'is', 'value' => 'x'];
		$this->manager->method('getOperations')->willReturn([$this->buildDbOperation()]);
		$this->manager->method('getRuntimeOperations')->willReturn([]);
		$this->manager->method('getChecks')->willReturn([$checkData]);

		$checkInstance = $this->createMock(ICheck::class);
		$checkInstance->method('executeCheck')->willReturn(false);
		$this->container->method('get')->willReturn($checkInstance);

		$this->ruleMatcher->setOperation($this->operation);
		$this->ruleMatcher->setEventName('MyEvent');

		$this->assertSame([], $this->ruleMatcher->getFlows(false));
	}

	public function testGetFlowsIncludesOperationWhenCheckPasses(): void {
		$checkData = ['class' => ICheck::class, 'operator' => 'is', 'value' => 'x'];
		$this->manager->method('getOperations')->willReturn([$this->buildDbOperation()]);
		$this->manager->method('getRuntimeOperations')->willReturn([]);
		$this->manager->method('getChecks')->willReturn([$checkData]);

		$checkInstance = $this->createMock(ICheck::class);
		$checkInstance->method('executeCheck')->willReturn(true);
		$this->container->method('get')->willReturn($checkInstance);

		$this->ruleMatcher->setOperation($this->operation);
		$this->ruleMatcher->setEventName('MyEvent');

		$results = $this->ruleMatcher->getFlows(false);
		$this->assertCount(1, $results);
	}

	public function testCheckWithPlainICheck(): void {
		$checkInstance = $this->createMock(ICheck::class);
		$checkInstance->expects($this->once())
			->method('executeCheck')
			->with('is', 'foo')
			->willReturn(true);
		$this->container->method('get')->willReturn($checkInstance);

		$this->assertTrue($this->ruleMatcher->check(['class' => ICheck::class, 'operator' => 'is', 'value' => 'foo']));
	}

	public function testCheckThrowsForInvalidCheckClass(): void {
		$this->container->method('get')->willReturn(new \stdClass());
		$this->expectException(UnexpectedValueException::class);
		$this->ruleMatcher->check(['class' => \stdClass::class, 'operator' => 'is', 'value' => 'x']);
	}

	public function testCheckWithFileCheckThrowsWithoutFileInfo(): void {
		$checkInstance = $this->createMock(IFileCheck::class);
		$this->container->method('get')->willReturn($checkInstance);

		$this->expectException(RuntimeException::class);
		$this->ruleMatcher->check(['class' => IFileCheck::class, 'operator' => 'is', 'value' => 'x']);
	}

	public function testCheckWithFileCheckPassesFileInfo(): void {
		$storage = $this->createMock(IStorage::class);
		$this->ruleMatcher->setFileInfo($storage, '/foo/bar.txt');

		$checkInstance = $this->createMock(FileCheckStub::class);
		$checkInstance->expects($this->once())
			->method('setFileInfo')
			->with($storage, '/foo/bar.txt', false);
		$checkInstance->method('executeCheck')->willReturn(true);
		$this->container->method('get')->willReturn($checkInstance);

		$this->assertTrue($this->ruleMatcher->check(['class' => IFileCheck::class, 'operator' => 'is', 'value' => 'x']));
	}
}

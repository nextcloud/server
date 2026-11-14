<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Governance;

use NCU\Governance\Exception\GovernanceUnavailableException;
use NCU\Governance\Exception\LabelPermissionException;
use NCU\Governance\Exception\ResourceNotFoundException;
use NCU\Governance\ILabelManager;
use NCU\Governance\LabelModification;
use NCU\Governance\LabelType;
use NCU\Governance\ResourceLabel;
use NCU\Resource\IResourceType;
use NCU\Resource\Permission\IPermissionManager;
use NCU\Resource\ResourceReference;
use OC\Governance\LabelManager;
use OCP\Actor\Actor;
use OCP\Actor\Principal;
use OCP\App\IAppManager;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class LabelManagerTest extends TestCase {
	private IAppManager&MockObject $appManager;
	private ContainerInterface&MockObject $container;
	private LoggerInterface&MockObject $logger;
	private ILabelManager&MockObject $backend;
	private LabelManager $manager;
	private Actor $alice;
	private ResourceReference $table;
	private ResourceReference $otherTable;

	/** @var object the service the container returns for the governance app */
	private object $service;

	protected function setUp(): void {
		parent::setUp();

		$this->alice = Actor::of(Principal::user('alice'));
		$this->table = new ResourceReference('tables:table', '1');
		$this->otherTable = new ResourceReference('tables:table', '2');

		$this->backend = $this->createMock(ILabelManager::class);
		$this->service = $this->backend;
		$this->container = $this->createMock(ContainerInterface::class);
		$this->container->method('get')->with(LabelManager::BACKEND)->willReturnCallback(fn (): object => $this->service);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->manager = $this->createManager(true);
	}

	private function createManager(bool $governanceEnabled): LabelManager {
		$this->appManager = $this->createMock(IAppManager::class);
		$this->appManager->method('isEnabledForAnyone')->with('governance')->willReturn($governanceEnabled);

		$permissionManager = $this->createMock(IPermissionManager::class);
		$permissionManager->method('getResourceType')->willReturnCallback(
			fn (string $id): ?IResourceType => $id === 'tables:table' ? $this->createMock(IResourceType::class) : null,
		);

		return new LabelManager($this->appManager, $this->container, $permissionManager, $this->logger);
	}

	private function createLabel(string $id, LabelType $type = LabelType::Sensitivity): ResourceLabel {
		return new ResourceLabel($id, $type, 'Label ' . $id, null, null, 0, true, LabelModification::Yes, LabelModification::Yes);
	}

	public function testIsRegistered(): void {
		$manager = Server::get(ILabelManager::class);

		$this->assertInstanceOf(LabelManager::class, $manager);
		$this->assertFalse($manager->isAvailable());
	}

	public function testIsAvailable(): void {
		$this->backend->method('isAvailable')->willReturn(true);

		$this->assertTrue($this->manager->isAvailable());
	}

	public function testWithoutGovernanceNoLabelsAreReturned(): void {
		$manager = $this->createManager(false);
		$this->container->expects($this->never())->method('get');

		$this->assertFalse($manager->isAvailable());
		$this->assertSame(['tables:table/1' => [], 'tables:table/2' => []], $manager->getAssignedLabelsForResources($this->alice, [$this->table, $this->otherTable]));
		$this->assertSame([], $manager->getAssignedLabels($this->alice, $this->table));
		$this->assertSame([], $manager->getAssignableLabels($this->alice, $this->table));
	}

	public function testWithoutGovernanceLabelsCannotBeAssigned(): void {
		$this->expectException(GovernanceUnavailableException::class);
		$this->createManager(false)->assignLabel($this->alice, $this->table, '1');
	}

	public function testWithoutGovernanceLabelsCannotBeRemoved(): void {
		$this->expectException(GovernanceUnavailableException::class);
		$this->createManager(false)->removeLabel($this->alice, $this->table, '1');
	}

	public function testServiceThatIsNoLabelManagerIsUnavailable(): void {
		$this->service = new \stdClass();
		$this->logger->expects($this->atLeastOnce())->method('error');

		$this->assertFalse($this->manager->isAvailable());
		$this->assertSame([], $this->manager->getAssignedLabels($this->alice, $this->table));
	}

	public function testServiceIsLoadedOnce(): void {
		$this->container->expects($this->once())->method('get');

		$this->manager->isAvailable();
		$this->manager->getAssignableLabels($this->alice, $this->table);
	}

	public function testUnregisteredResourceType(): void {
		$this->backend->expects($this->never())->method('getAssignedLabelsForResources');

		$this->expectException(\InvalidArgumentException::class);
		$this->manager->getAssignedLabelsForResources($this->alice, [$this->table, new ResourceReference('forms:form', '1')]);
	}

	public function testUnregisteredResourceTypeWithoutGovernance(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->createManager(false)->getAssignableLabels($this->alice, new ResourceReference('forms:form', '1'));
	}

	public function testLabelsCannotBeAssignedToAnUnregisteredResourceType(): void {
		$this->backend->expects($this->never())->method('assignLabel');

		$this->expectException(\InvalidArgumentException::class);
		$this->manager->assignLabel($this->alice, new ResourceReference('forms:form', '1'), '1');
	}

	/**
	 * The governance app decides which labels the actor sees, every resource is passed on.
	 */
	public function testAssignedLabelsAreReadFromGovernance(): void {
		$internal = $this->createLabel('1');
		$hold = $this->createLabel('3', LabelType::Hold);
		$this->backend->expects($this->once())->method('getAssignedLabelsForResources')
			->with($this->alice, [$this->otherTable, $this->table])
			->willReturn([
				'tables:table/1' => [5 => $internal, 7 => $hold],
				// A resource that was not asked for
				'tables:table/99' => [$hold],
			]);

		$labels = $this->manager->getAssignedLabelsForResources($this->alice, [$this->otherTable, $this->table]);

		$this->assertSame(['tables:table/2' => [], 'tables:table/1' => [$internal, $hold]], $labels);
	}

	public function testGovernanceIsNotAskedWithoutResources(): void {
		$this->backend->expects($this->never())->method('getAssignedLabelsForResources');

		$this->assertSame([], $this->manager->getAssignedLabelsForResources($this->alice, []));
	}

	public function testGetAssignedLabels(): void {
		$internal = $this->createLabel('1');
		$this->backend->method('getAssignedLabelsForResources')->willReturn(['tables:table/1' => [$internal]]);

		$this->assertSame([$internal], $this->manager->getAssignedLabels($this->alice, $this->table));
	}

	public function testAssignableLabelsAreReadFromGovernance(): void {
		$labels = [$this->createLabel('1'), $this->createLabel('2')];
		$this->backend->expects($this->once())->method('getAssignableLabels')
			->with($this->alice, $this->table, LabelType::Sensitivity)
			->willReturn($labels);

		$this->assertSame($labels, $this->manager->getAssignableLabels($this->alice, $this->table, LabelType::Sensitivity));
	}

	public function testAssignLabel(): void {
		$this->backend->expects($this->once())->method('assignLabel')->with($this->alice, $this->table, '1', 'Contains salaries');

		$this->manager->assignLabel($this->alice, $this->table, '1', 'Contains salaries');
	}

	public function testRemoveLabel(): void {
		$this->backend->expects($this->once())->method('removeLabel')->with($this->alice, $this->table, '1', 'Outdated');

		$this->manager->removeLabel($this->alice, $this->table, '1', 'Outdated');
	}

	public function testExceptionsOfGovernanceArePassedOn(): void {
		$this->backend->method('assignLabel')->willThrowException(new ResourceNotFoundException());
		$this->backend->method('removeLabel')->willThrowException(new LabelPermissionException());

		try {
			$this->manager->assignLabel($this->alice, $this->table, '1');
			$this->fail('Expected a ResourceNotFoundException');
		} catch (ResourceNotFoundException) {
		}

		$this->expectException(LabelPermissionException::class);
		$this->manager->removeLabel($this->alice, $this->table, '1');
	}
}

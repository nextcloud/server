<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Governance;

use NCU\Governance\Events\LabelAssignedEvent;
use NCU\Governance\Events\LabelRemovedEvent;
use NCU\Governance\Exception\GovernanceUnavailableException;
use NCU\Governance\Exception\LabelNotFoundException;
use NCU\Governance\Exception\LabelPermissionException;
use NCU\Governance\Exception\ResourceNotFoundException;
use NCU\Governance\ILabelManager;
use NCU\Governance\LabelModification;
use NCU\Governance\LabelType;
use NCU\Governance\ResourceLabel;
use NCU\Resource\Permission\AccessEntry;
use NCU\Resource\Permission\CorePermission;
use NCU\Resource\Permission\IAccessManager;
use NCU\Resource\ResourceReference;
use OC\Governance\LabelManager;
use OCP\Actor\Actor;
use OCP\Actor\Principal;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class LabelManagerTest extends TestCase {
	private const string INTERNAL = '1';
	private const string CONFIDENTIAL = '2';
	private const string HOLD = '3';

	private IAppManager&MockObject $appManager;
	private ContainerInterface&MockObject $container;
	private IAccessManager&MockObject $accessManager;
	private LoggerInterface&MockObject $logger;
	private LabelManager $manager;
	private Actor $alice;
	private ResourceReference $table;
	private ResourceReference $hidden;

	/** @var list<Event> */
	private array $events = [];

	/** @var ILabelManager&object{assigned: array<string, list<string>>, calls: list<array>} */
	private object $backend;

	protected function setUp(): void {
		parent::setUp();

		$this->alice = Actor::of(Principal::user('alice'));
		$this->table = new ResourceReference('tables:table', '1');
		$this->hidden = new ResourceReference('tables:table', '2');

		$this->appManager = $this->createMock(IAppManager::class);
		$this->appManager->method('isEnabledForAnyone')->with('governance')->willReturn(true);

		$this->backend = $this->createBackend();
		$this->container = $this->createMock(ContainerInterface::class);
		$this->container->method('get')->with(LabelManager::BACKEND)->willReturnCallback(fn (): object => $this->backend);

		// Alice has access to every resource but the hidden table
		$this->accessManager = $this->createMock(IAccessManager::class);
		$this->accessManager->method('getAccess')->willReturnCallback(function (Actor $actor, array $resources): array {
			$access = [];
			foreach ($resources as $resource) {
				if ($resource->type !== 'tables:table') {
					throw new \InvalidArgumentException('The resource type ' . $resource->type . ' is not registered');
				}
				if ($resource->key() !== $this->hidden->key()) {
					$access[$resource->key()] = [new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Read->value])];
				}
			}
			return $access;
		});

		$eventDispatcher = $this->createMock(IEventDispatcher::class);
		$eventDispatcher->method('dispatchTyped')->willReturnCallback(function (Event $event): void {
			$this->events[] = $event;
		});

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->manager = new LabelManager($this->appManager, $this->container, $this->accessManager, $eventDispatcher, $this->logger);
	}

	/**
	 * A label manager with two sensitivity labels, of which a resource has at most one, and a hold label.
	 */
	private function createBackend(): ILabelManager {
		return new class implements ILabelManager {
			private const array LABELS = [
				'1' => [LabelType::Sensitivity, 'Internal'],
				'2' => [LabelType::Sensitivity, 'Confidential'],
				'3' => [LabelType::Hold, 'Audit 2026'],
			];

			/** @var array<string, list<string>> label IDs by resource key */
			public array $assigned = [];

			/** @var list<array> */
			public array $calls = [];

			public function isAvailable(): bool {
				return true;
			}

			public function getAssignedLabelsForResources(Actor $actor, array $resources): array {
				$this->calls[] = ['getAssignedLabelsForResources', $resources];
				$labels = [];
				foreach ($resources as $resource) {
					$labels[$resource->key()] = array_map($this->createLabel(...), $this->assigned[$resource->key()] ?? []);
				}
				// A resource that was not asked for
				$labels['tables:table/99'] = [$this->createLabel('3')];
				return $labels;
			}

			public function getAssignedLabels(Actor $actor, ResourceReference $resource): array {
				return array_map($this->createLabel(...), $this->assigned[$resource->key()] ?? []);
			}

			public function getAssignableLabels(Actor $actor, ResourceReference $resource, ?LabelType $type = null): array {
				$this->calls[] = ['getAssignableLabels', $resource, $type];
				return array_values(array_filter(
					array_map($this->createLabel(...), array_map(strval(...), array_keys(self::LABELS))),
					static fn (ResourceLabel $label): bool => $type === null || $label->type === $type,
				));
			}

			public function assignLabel(Actor $actor, ResourceReference $resource, string $labelId, ?string $reason = null): void {
				$this->calls[] = ['assignLabel', $resource, $labelId, $reason];
				if (!isset(self::LABELS[$labelId])) {
					throw new LabelNotFoundException();
				}

				$assigned = $this->assigned[$resource->key()] ?? [];
				if (self::LABELS[$labelId][0] === LabelType::Sensitivity) {
					$assigned = array_filter($assigned, static fn (string $id): bool => self::LABELS[$id][0] !== LabelType::Sensitivity);
				}
				$this->assigned[$resource->key()] = array_values(array_unique([...$assigned, $labelId]));
			}

			public function removeLabel(Actor $actor, ResourceReference $resource, string $labelId, ?string $reason = null): void {
				$this->calls[] = ['removeLabel', $resource, $labelId, $reason];
				if (self::LABELS[$labelId][0] === LabelType::Hold) {
					throw new LabelPermissionException();
				}
				$this->assigned[$resource->key()] = array_values(array_diff($this->assigned[$resource->key()] ?? [], [$labelId]));
			}

			private function createLabel(string $id): ResourceLabel {
				return new ResourceLabel($id, self::LABELS[$id][0], self::LABELS[$id][1], null, null, (int)$id, true, LabelModification::Yes, LabelModification::Yes);
			}
		};
	}

	private function disableGovernance(): void {
		$this->appManager = $this->createMock(IAppManager::class);
		$this->appManager->method('isEnabledForAnyone')->willReturn(false);
		$this->container->expects($this->never())->method('get');
		$this->manager = new LabelManager($this->appManager, $this->container, $this->accessManager, $this->createMock(IEventDispatcher::class), $this->logger);
	}

	/**
	 * @return list<array{class-string, string, LabelType}>
	 */
	private function getDispatched(): array {
		return array_map(static fn (LabelAssignedEvent|LabelRemovedEvent $event): array => [$event::class, $event->labelId, $event->labelType], $this->events);
	}

	public function testIsRegistered(): void {
		$manager = Server::get(ILabelManager::class);

		$this->assertInstanceOf(LabelManager::class, $manager);
		$this->assertFalse($manager->isAvailable());
	}

	public function testWithoutGovernanceNoLabelsAreReturned(): void {
		$this->disableGovernance();

		$this->assertFalse($this->manager->isAvailable());
		$this->assertSame(['tables:table/1' => [], 'tables:table/2' => []], $this->manager->getAssignedLabelsForResources($this->alice, [$this->table, $this->hidden]));
		$this->assertSame([], $this->manager->getAssignedLabels($this->alice, $this->table));
		$this->assertSame([], $this->manager->getAssignableLabels($this->alice, $this->table));
	}

	public function testWithoutGovernanceLabelsCannotBeAssigned(): void {
		$this->disableGovernance();

		$this->expectException(GovernanceUnavailableException::class);
		$this->manager->assignLabel($this->alice, $this->table, self::INTERNAL);
	}

	public function testWithoutGovernanceLabelsCannotBeRemoved(): void {
		$this->disableGovernance();

		$this->expectException(GovernanceUnavailableException::class);
		$this->manager->removeLabel($this->alice, $this->table, self::INTERNAL);
	}

	public function testBackendThatCannotBeLoadedIsUnavailable(): void {
		$this->backend = new \stdClass();
		$this->logger->expects($this->atLeastOnce())->method('error');

		$this->assertFalse($this->manager->isAvailable());
		$this->assertSame([], $this->manager->getAssignedLabels($this->alice, $this->table));
	}

	public function testIsAvailable(): void {
		$this->assertTrue($this->manager->isAvailable());
	}

	public function testUnregisteredResourceType(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->manager->getAssignedLabels($this->alice, new ResourceReference('forms:form', '1'));
	}

	public function testUnregisteredResourceTypeWithoutGovernance(): void {
		$this->disableGovernance();

		$this->expectException(\InvalidArgumentException::class);
		$this->manager->getAssignedLabelsForResources($this->alice, [new ResourceReference('forms:form', '1')]);
	}

	public function testAssignedLabelsOfResourcesWithoutAccessAreNotRead(): void {
		$this->backend->assigned = [
			'tables:table/1' => [self::INTERNAL, self::HOLD],
			'tables:table/2' => [self::CONFIDENTIAL],
		];
		$other = new ResourceReference('tables:table', '3');

		$labels = $this->manager->getAssignedLabelsForResources($this->alice, [$this->hidden, $this->table, $other]);

		$this->assertSame(['tables:table/2', 'tables:table/1', 'tables:table/3'], array_keys($labels));
		$this->assertSame([self::INTERNAL, self::HOLD], array_column($labels['tables:table/1'], 'id'));
		$this->assertSame([], $labels['tables:table/2']);
		$this->assertSame([], $labels['tables:table/3']);
		$this->assertEquals([['getAssignedLabelsForResources', [$this->table, $other]]], $this->backend->calls);

		$this->assertSame([], $this->manager->getAssignedLabels($this->alice, $this->hidden));
		$this->assertCount(1, $this->backend->calls);
	}

	public function testAssignableLabels(): void {
		$this->assertSame(
			[self::INTERNAL, self::CONFIDENTIAL],
			array_column($this->manager->getAssignableLabels($this->alice, $this->table, LabelType::Sensitivity), 'id'),
		);
		$this->assertCount(3, $this->manager->getAssignableLabels($this->alice, $this->table));

		$this->assertSame([], $this->manager->getAssignableLabels($this->alice, $this->hidden));
		$this->assertCount(2, $this->backend->calls);
	}

	public function testAssignLabel(): void {
		$this->manager->assignLabel($this->alice, $this->table, self::INTERNAL, 'Contains salaries');

		$this->assertSame([self::INTERNAL], $this->backend->assigned['tables:table/1']);
		$this->assertEquals([['assignLabel', $this->table, self::INTERNAL, 'Contains salaries']], $this->backend->calls);
		$this->assertSame([[LabelAssignedEvent::class, self::INTERNAL, LabelType::Sensitivity]], $this->getDispatched());
		$this->assertSame($this->alice, $this->events[0]->actor);
		$this->assertSame($this->table, $this->events[0]->resource);
	}

	public function testAssigningAnAssignedLabelDispatchesNothing(): void {
		$this->backend->assigned['tables:table/1'] = [self::INTERNAL];

		$this->manager->assignLabel($this->alice, $this->table, self::INTERNAL);

		$this->assertSame([], $this->events);
	}

	public function testReplacedLabelIsRemovedBeforeTheNewOneIsAssigned(): void {
		$this->backend->assigned['tables:table/1'] = [self::HOLD, self::INTERNAL];

		$this->manager->assignLabel($this->alice, $this->table, self::CONFIDENTIAL);

		$this->assertSame([
			[LabelRemovedEvent::class, self::INTERNAL, LabelType::Sensitivity],
			[LabelAssignedEvent::class, self::CONFIDENTIAL, LabelType::Sensitivity],
		], $this->getDispatched());
	}

	public function testRemoveLabel(): void {
		$this->backend->assigned['tables:table/1'] = [self::INTERNAL];

		$this->manager->removeLabel($this->alice, $this->table, self::INTERNAL, 'Outdated');

		$this->assertSame([], $this->backend->assigned['tables:table/1']);
		$this->assertEquals([['removeLabel', $this->table, self::INTERNAL, 'Outdated']], $this->backend->calls);
		$this->assertSame([[LabelRemovedEvent::class, self::INTERNAL, LabelType::Sensitivity]], $this->getDispatched());
	}

	public function testAssignLabelWithoutAccess(): void {
		try {
			$this->manager->assignLabel($this->alice, $this->hidden, self::INTERNAL);
			$this->fail('Expected a ResourceNotFoundException');
		} catch (ResourceNotFoundException) {
		}

		$this->assertSame([], $this->backend->calls);
		$this->assertSame([], $this->events);
	}

	public function testRemoveLabelWithoutAccess(): void {
		$this->backend->assigned['tables:table/2'] = [self::INTERNAL];

		try {
			$this->manager->removeLabel($this->alice, $this->hidden, self::INTERNAL);
			$this->fail('Expected a ResourceNotFoundException');
		} catch (ResourceNotFoundException) {
		}

		$this->assertSame([self::INTERNAL], $this->backend->assigned['tables:table/2']);
		$this->assertSame([], $this->backend->calls);
	}

	public function testExceptionsOfTheBackendArePassedOn(): void {
		$this->backend->assigned['tables:table/1'] = [self::HOLD];

		try {
			$this->manager->assignLabel($this->alice, $this->table, 'unknown');
			$this->fail('Expected a LabelNotFoundException');
		} catch (LabelNotFoundException) {
		}
		try {
			$this->manager->removeLabel($this->alice, $this->table, self::HOLD);
			$this->fail('Expected a LabelPermissionException');
		} catch (LabelPermissionException) {
		}

		$this->assertSame([], $this->events);
	}
}

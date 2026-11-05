<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Permission;

use NCU\Permission\ABooleanPermission;
use NCU\Permission\CorePermission;
use NCU\Permission\IPermission;
use NCU\Permission\IPermissionManager;
use OC\Permission\PermissionManager;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PermissionManagerTest extends TestCase {
	private ContainerInterface&MockObject $container;
	private LoggerInterface&MockObject $logger;
	private PermissionManager $manager;

	/** @var array<string, object> */
	private array $instances = [];

	protected function setUp(): void {
		parent::setUp();

		$this->container = $this->createMock(ContainerInterface::class);
		$this->container->method('get')->willReturnCallback(function (string $class): object {
			return $this->instances[$class] ?? throw new class('Not found: ' . $class) extends \Exception implements NotFoundExceptionInterface {
			};
		});
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->manager = new PermissionManager($this->container, $this->logger);
	}

	/**
	 * @param list<string> $requiredPermissionIds
	 */
	private function registerPermission(string $id, int $priority = 50, string $defaultValue = 'true', array $requiredPermissionIds = [], ?string $parentId = null): IPermission {
		$permission = new class($id, $priority, $defaultValue, $requiredPermissionIds, $parentId) extends ABooleanPermission {
			public function __construct(
				private readonly string $id,
				private readonly int $priority,
				private readonly string $defaultValue,
				private readonly array $requiredPermissionIds,
				private readonly ?string $parentId,
			) {
			}

			public function getParentPermissionId(): ?string {
				return $this->parentId;
			}

			public function getId(): string {
				return $this->id;
			}

			public function getDisplayName(): string {
				return 'Permission ' . $this->id;
			}

			public function getPriority(): int {
				return $this->priority;
			}

			public function getDefaultValue(): string {
				return $this->defaultValue;
			}

			public function getRequiredPermissionIds(): array {
				return $this->requiredPermissionIds;
			}
		};

		$class = 'Permission\\' . count($this->instances);
		$this->instances[$class] = $permission;
		$this->manager->registerPermission($class);
		return $permission;
	}

	public function testPermissionsAreInstantiatedLazilyAndOnce(): void {
		$this->registerPermission('app:a');
		$this->registerPermission('app:b');

		$this->container->expects($this->exactly(2))->method('get');

		$this->manager->getPermissions();
		$this->manager->getPermission('app:a');
		$this->manager->getPermissions();
	}

	public function testRegisteringAfterLoadingReloads(): void {
		$this->registerPermission('app:a');
		$this->assertSame(['app:a'], array_keys($this->manager->getPermissions()));

		$this->registerPermission('app:b');
		$this->assertSame(['app:a', 'app:b'], array_keys($this->manager->getPermissions()));
	}

	public function testPermissionsAreSortedByPriorityAndId(): void {
		$this->registerPermission('app:c', 10);
		$this->registerPermission('app:b', 90);
		$this->registerPermission('app:a', 10);

		$this->assertSame(['app:b', 'app:a', 'app:c'], array_keys($this->manager->getPermissions()));
	}

	public function testGetPermission(): void {
		$permission = $this->registerPermission('app:a');

		$this->assertSame($permission, $this->manager->getPermission('app:a'));
		$this->assertNull($this->manager->getPermission('app:unknown'));
	}

	public function testInvalidPermissionsAreSkipped(): void {
		$valid = $this->registerPermission('app:valid');
		$this->registerPermission('Invalid ID');
		$this->registerPermission('app:valid', 10);
		$this->registerPermission('app:invalid_default', defaultValue: 'maybe');
		$this->instances['Permission\\wrong'] = new \stdClass();
		$this->manager->registerPermission('Permission\\wrong');
		$this->manager->registerPermission('Permission\\missing');

		$this->logger->expects($this->exactly(5))->method('error');

		$this->assertSame(['app:valid' => $valid], $this->manager->getPermissions());
	}

	public function testInvalidRequiredPermissionsAreLogged(): void {
		$this->registerPermission('app:read');
		$this->registerPermission('app:update', requiredPermissionIds: ['app:read', 'app:unknown', 'app:update']);

		$this->logger->expects($this->exactly(2))->method('warning');

		$this->assertNotNull($this->manager->getPermission('app:update'));
	}

	public function testChildPermissions(): void {
		$this->registerPermission('core:share');
		$forward = $this->registerPermission('mail:forward', 10, parentId: 'core:share');
		$shareTable = $this->registerPermission('tables:share', 90, parentId: 'core:share');
		$this->registerPermission('app:other');

		$this->assertSame([$shareTable, $forward], $this->manager->getChildPermissions('core:share'));
		$this->assertSame([], $this->manager->getChildPermissions('mail:forward'));
		$this->assertSame([], $this->manager->getChildPermissions('app:other'));
		$this->assertSame([], $this->manager->getChildPermissions('app:unknown'));
	}

	public function testInvalidParentsAreIgnored(): void {
		$this->registerPermission('core:share');
		$this->registerPermission('mail:forward', parentId: 'core:share');
		$this->registerPermission('mail:forward_external', parentId: 'mail:forward');
		$this->registerPermission('app:self', parentId: 'app:self');
		$this->registerPermission('app:orphan', parentId: 'app:unknown');

		$this->logger->expects($this->exactly(2))->method('error');
		$this->logger->expects($this->once())->method('warning');

		$this->assertCount(5, $this->manager->getPermissions());
		$this->assertCount(1, $this->manager->getChildPermissions('core:share'));
		$this->assertSame([], $this->manager->getChildPermissions('mail:forward'));
		$this->assertSame([], $this->manager->getChildPermissions('app:self'));
		$this->assertSame([], $this->manager->getChildPermissions('app:unknown'));
	}

	public function testCorePermissionsAreRegistered(): void {
		$manager = Server::get(IPermissionManager::class);

		foreach (CorePermission::cases() as $corePermission) {
			$permission = $manager->getPermission($corePermission->value);
			$this->assertNotNull($permission, $corePermission->value . ' is not registered');
			if ($corePermission !== CorePermission::Read) {
				$this->assertSame([CorePermission::Read->value], $permission->getRequiredPermissionIds());
			}
		}
	}
}

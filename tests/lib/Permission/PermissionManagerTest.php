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
use NCU\Permission\IPermissionResourceType;
use NCU\Permission\PermissionBinding;
use NCU\Permission\ResolvedPermission;
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
	private function registerPermission(string $id, int $priority = 50, string $defaultValue = 'true', array $requiredPermissionIds = []): IPermission {
		$permission = new class($id, $priority, $defaultValue, $requiredPermissionIds) extends ABooleanPermission {
			public function __construct(
				private readonly string $id,
				private readonly int $priority,
				private readonly string $defaultValue,
				private readonly array $requiredPermissionIds,
			) {
			}

			public function getId(): string {
				return $this->id;
			}

			public function getDisplayName(): string {
				return 'Generic ' . $this->id;
			}

			public function getHint(): ?string {
				return 'Generic hint ' . $this->id;
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

	/**
	 * @param list<PermissionBinding> $bindings
	 */
	private function registerResourceType(string $id, array $bindings): IPermissionResourceType {
		$resourceType = $this->createMock(IPermissionResourceType::class);
		$resourceType->method('getId')->willReturn($id);
		$resourceType->method('getDisplayName')->willReturn('Resource ' . $id);
		$resourceType->method('getPermissionBindings')->willReturn($bindings);

		$class = 'ResourceType\\' . count($this->instances);
		$this->instances[$class] = $resourceType;
		$this->manager->registerResourceType($class);
		return $resourceType;
	}

	/**
	 * @param list<ResolvedPermission> $resolvedPermissions
	 * @return list<string>
	 */
	private static function ids(array $resolvedPermissions): array {
		return array_map(static fn (ResolvedPermission $resolvedPermission): string => $resolvedPermission->permission->getId(), $resolvedPermissions);
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
		$this->assertNull($this->manager->getPermission('app:invalid_default'));
	}

	public function testUnknownRequiredPermissionIsLogged(): void {
		$this->registerPermission('app:a', requiredPermissionIds: ['app:unknown']);

		$this->logger->expects($this->once())->method('warning');

		$this->assertNotNull($this->manager->getPermission('app:a'));
	}

	public function testInvalidResourceTypesAreSkipped(): void {
		$valid = $this->registerResourceType('app:valid', []);
		$this->registerResourceType('Invalid ID', []);
		$this->registerResourceType('app:valid', []);
		$this->instances['ResourceType\\wrong'] = new \stdClass();
		$this->manager->registerResourceType('ResourceType\\wrong');

		$this->logger->expects($this->exactly(3))->method('error');

		$this->assertSame(['app:valid' => $valid], $this->manager->getResourceTypes());
		$this->assertSame($valid, $this->manager->getResourceType('app:valid'));
		$this->assertNull($this->manager->getResourceType('Invalid ID'));
	}

	public function testBindingOverridesAndFallbacks(): void {
		$read = $this->registerPermission('app:read', 90);
		$share = $this->registerPermission('app:share', 50, 'true');
		$this->registerResourceType('app:resource', [
			new PermissionBinding('app:share', 'Forward', 'Forward hint', 'false'),
			new PermissionBinding('app:read'),
		]);

		$resolved = $this->manager->getPermissionsForResourceType('app:resource');

		$this->assertEquals([
			new ResolvedPermission($read, 'app:resource', 'Generic app:read', 'Generic hint app:read', 'true', []),
			new ResolvedPermission($share, 'app:resource', 'Forward', 'Forward hint', 'false', []),
		], $resolved);
	}

	public function testInvalidBindingsAreSkipped(): void {
		$this->registerPermission('app:a');
		$this->registerPermission('app:b');
		$this->registerResourceType('app:resource', [
			new PermissionBinding('app:a'),
			new PermissionBinding('app:a', 'Duplicate'),
			new PermissionBinding('app:b', defaultValue: 'maybe'),
			new PermissionBinding('app:unknown'),
		]);

		$this->logger->expects($this->exactly(2))->method('error');
		$this->logger->expects($this->once())->method('warning');

		$resolved = $this->manager->getPermissionsForResourceType('app:resource');
		$this->assertSame(['app:a'], self::ids($resolved));
		$this->assertSame('Generic app:a', $resolved[0]->displayName);
	}

	public function testRequiredPermissionsAreLimitedToBoundPermissions(): void {
		$this->registerPermission('app:read');
		$this->registerPermission('app:list');
		$this->registerPermission('app:update', requiredPermissionIds: ['app:read', 'app:list', 'app:update']);
		$this->registerResourceType('app:resource', [
			new PermissionBinding('app:read'),
			new PermissionBinding('app:update'),
		]);

		$resolved = $this->manager->getPermissionsForResourceType('app:resource');

		$this->assertSame(['app:read', 'app:update'], self::ids($resolved));
		$this->assertSame([], $resolved[0]->requiredPermissionIds);
		$this->assertSame(['app:read'], $resolved[1]->requiredPermissionIds);
	}

	public function testGetResourceTypesForPermission(): void {
		$this->registerPermission('app:share');
		$this->registerPermission('app:other');
		$this->registerResourceType('mail:message', [new PermissionBinding('app:share', 'Forward')]);
		$this->registerResourceType('files:node', [new PermissionBinding('app:share'), new PermissionBinding('app:other')]);
		$this->registerResourceType('tables:table', [new PermissionBinding('app:other')]);

		$resolved = $this->manager->getResourceTypesForPermission('app:share');

		$this->assertSame(['files:node', 'mail:message'], array_map(static fn (ResolvedPermission $resolvedPermission): string => $resolvedPermission->resourceTypeId, $resolved));
		$this->assertSame(['Generic app:share', 'Forward'], array_map(static fn (ResolvedPermission $resolvedPermission): string => $resolvedPermission->displayName, $resolved));
		$this->assertSame([], $this->manager->getResourceTypesForPermission('app:unknown'));
		$this->assertSame([], $this->manager->getPermissionsForResourceType('app:unknown'));
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

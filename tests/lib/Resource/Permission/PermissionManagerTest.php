<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Resource\Permission;

use NCU\Resource\IResourceType;
use NCU\Resource\Permission\AccessEntry;
use NCU\Resource\Permission\APermission;
use NCU\Resource\Permission\CorePermission;
use NCU\Resource\Permission\IAccessManager;
use NCU\Resource\Permission\IPermission;
use NCU\Resource\Permission\IPermissionManager;
use NCU\Resource\Permission\IRestrictionProvider;
use NCU\Resource\Permission\ResourceAccess;
use NCU\Resource\Permission\Restriction;
use NCU\Resource\ResourceReference;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\Resource\Permission\AccessManager;
use OC\Resource\Permission\PermissionManager;
use OCP\Actor\Actor;
use OCP\Actor\Principal;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PermissionManagerTest extends TestCase {
	private const string TABLE = 'tables:table';
	private const string ROW = 'tables:row';
	private const string EXPORT = 'tables:export';

	private RegistrationContext $context;
	private LoggerInterface&MockObject $logger;
	private PermissionManager $manager;
	private AccessManager $accessManager;
	private Actor $alice;

	/** @var array<string, object> */
	private array $instances = [];

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->context = new RegistrationContext($this->createMock(LoggerInterface::class));

		$coordinator = $this->createMock(Coordinator::class);
		$coordinator->method('getRegistrationContext')->willReturn($this->context);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(function (string $class): object {
			return $this->instances[$class] ?? throw new class('Not found: ' . $class) extends \Exception implements NotFoundExceptionInterface {
			};
		});

		$this->accessManager = new AccessManager($container, $this->logger);
		$this->manager = new PermissionManager($coordinator, $container, $this->logger, $this->accessManager);
		$this->instances[IPermissionManager::class] = $this->manager;
		$this->alice = Actor::of(Principal::user('alice'));
	}

	private function registerPermission(string $appId, string $id, ?CorePermission $kindOf): void {
		$this->instances[$id] = new class($id, $kindOf) implements IPermission {
			public function __construct(
				private readonly string $id,
				private readonly ?CorePermission $kindOf,
			) {
			}

			public function getId(): string {
				return $this->id;
			}

			public function getDisplayName(): string {
				return 'Permission ' . $this->id;
			}

			public function getHint(): ?string {
				return null;
			}

			public function getKindOf(): ?CorePermission {
				return $this->kindOf;
			}
		};
		$this->context->registerResourcePermission($appId, $id);
	}

	private function registerCorePermissions(): void {
		foreach (CorePermission::cases() as $permission) {
			$this->registerPermission('core', $permission->value, null);
		}
		$this->registerPermission('core', CorePermission::DOWNLOAD, CorePermission::Read);
	}

	/**
	 * @param list<string> $permissionIds
	 * @param ?\Closure(Actor, list<ResourceReference>): array<string, list<AccessEntry>> $access defaults to all permissions for every user
	 * @return object{calls: list<list<ResourceReference>>}&IResourceType
	 */
	private function registerResourceType(string $appId, string $id, array $permissionIds, ?\Closure $access = null): IResourceType {
		$access ??= static function (Actor $actor, array $resources) use ($permissionIds): array {
			$entries = [];
			foreach ($resources as $resource) {
				$entries[$resource->key()] = [new AccessEntry(AccessEntry::RECIPIENT_USER, $actor->principal->id, $permissionIds)];
			}
			return $entries;
		};
		$resourceType = new class($id, $permissionIds, $access) implements IResourceType {
			/** @var list<list<ResourceReference>> */
			public array $calls = [];

			public function __construct(
				private readonly string $id,
				private readonly array $permissionIds,
				private readonly \Closure $access,
			) {
			}

			public function getAccess(Actor $actor, array $resources): array {
				$this->calls[] = $resources;
				return ($this->access)($actor, $resources);
			}

			public function getId(): string {
				return $this->id;
			}

			public function getDisplayName(): string {
				return 'Resource type ' . $this->id;
			}

			public function getPermissionIds(): array {
				return $this->permissionIds;
			}
		};
		$this->instances[$id] = $resourceType;
		$this->context->registerResourceType($appId, $id);

		return $resourceType;
	}

	/**
	 * Core permissions, the permission tables:export as a kind of share, and the resource types
	 * tables:table (read, download, update, share, export) and tables:row (read, update, delete).
	 * Every user has access to all tables and rows with all permissions.
	 */
	private function registerTables(): void {
		$this->registerCorePermissions();
		$this->registerPermission('tables', self::EXPORT, CorePermission::Share);
		$this->registerResourceType('tables', self::TABLE, [
			CorePermission::Read->value,
			CorePermission::DOWNLOAD,
			CorePermission::Update->value,
			CorePermission::Share->value,
			self::EXPORT,
		]);
		$this->registerResourceType('tables', self::ROW, [
			CorePermission::Read->value,
			CorePermission::Update->value,
			CorePermission::Delete->value,
		]);
	}

	/**
	 * @param \Closure(Actor, list<ResourceAccess>): iterable<Restriction> $restrictions
	 * @return object{calls: list<list<ResourceAccess>>}&IRestrictionProvider
	 */
	private function registerRestrictionProvider(\Closure $restrictions): IRestrictionProvider {
		$provider = new class($restrictions) implements IRestrictionProvider {
			/** @var list<list<ResourceAccess>> */
			public array $calls = [];

			public function __construct(
				private readonly \Closure $restrictions,
			) {
			}

			public function getRestrictions(Actor $actor, array $accesses): iterable {
				$this->calls[] = $accesses;
				return ($this->restrictions)($actor, $accesses);
			}
		};
		$class = 'provider' . count($this->instances);
		$this->instances[$class] = $provider;
		$this->context->registerResourceRestrictionProvider('governance', $class);

		return $provider;
	}

	/**
	 * @param list<string> $expected
	 */
	private function assertAllowed(array $expected, ResourceReference $resource, ?array $entries = null): void {
		$allowed = $this->manager->check($this->alice, $resource, $entries)->getAllowedPermissionIds();
		sort($allowed);
		sort($expected);
		$this->assertSame($expected, $allowed);
	}

	public function testCorePermissionsAreRegistered(): void {
		$manager = Server::get(IPermissionManager::class);

		foreach (CorePermission::cases() as $permission) {
			$this->assertNull($manager->getPermission($permission->value)?->getKindOf(), $permission->value);
			$this->assertNotNull($manager->getPermission($permission->value), $permission->value);
		}
		$this->assertSame(CorePermission::Read, $manager->getPermission(CorePermission::DOWNLOAD)?->getKindOf());
	}

	public function testGetPermissions(): void {
		$this->registerTables();

		$this->assertSame(
			[...array_column(CorePermission::cases(), 'value'), CorePermission::DOWNLOAD, self::EXPORT],
			array_keys($this->manager->getPermissions()),
		);
		$this->assertSame(CorePermission::Share, $this->manager->getPermission(self::EXPORT)?->getKindOf());
		$this->assertNull($this->manager->getPermission('tables:unknown'));
	}

	public function testAppPermissionBaseClassRequiresKind(): void {
		$permission = new class extends APermission {
			public function getId(): string {
				return 'tables:export';
			}

			public function getDisplayName(): string {
				return 'Export';
			}

			public function getKindOf(): CorePermission {
				return CorePermission::Share;
			}
		};

		$this->assertNull($permission->getHint());
		$this->assertSame(CorePermission::Share, $permission->getKindOf());
	}

	public static function dataInvalidPermissions(): array {
		return [
			'foreign app prefix' => ['tables', 'forms:results', CorePermission::Read],
			'core prefix from an app' => ['tables', 'core:print', CorePermission::Read],
			'missing name' => ['tables', 'tables:', CorePermission::Read],
			'invalid name' => ['tables', 'tables:Export All', CorePermission::Read],
			'app permission without kind' => ['tables', 'tables:export', null],
			'core permission with kind' => ['core', 'core:update', CorePermission::Read],
			'unknown core permission without kind' => ['core', 'core:print', null],
		];
	}

	#[DataProvider('dataInvalidPermissions')]
	public function testInvalidPermissionIsSkipped(string $appId, string $id, ?CorePermission $kindOf): void {
		$this->logger->expects($this->once())->method('error');
		$this->registerPermission($appId, $id, $kindOf);

		$this->assertSame([], $this->manager->getPermissions());
	}

	public function testDuplicatePermissionIsSkipped(): void {
		$this->registerPermission('tables', self::EXPORT, CorePermission::Share);
		$first = $this->instances[self::EXPORT];
		$this->instances['duplicate'] = clone $first;
		$this->context->registerResourcePermission('tables', 'duplicate');
		$this->logger->expects($this->once())->method('error');

		$this->assertSame([self::EXPORT => $first], $this->manager->getPermissions());
	}

	public function testBrokenRegistrationsAreSkipped(): void {
		$this->context->registerResourcePermission('tables', 'missing');
		$this->instances['wrong'] = new \stdClass();
		$this->context->registerResourcePermission('tables', 'wrong');
		$this->context->registerResourceType('tables', 'missing');
		$this->registerTables();
		$this->logger->expects($this->exactly(3))->method('error');

		$this->assertCount(7, $this->manager->getPermissions());
		$this->assertCount(2, $this->manager->getResourceTypes());
		$this->assertAllowed([CorePermission::Read->value, CorePermission::Update->value, CorePermission::Delete->value], new ResourceReference(self::ROW, '1'));
	}

	public function testGetResourceTypes(): void {
		$this->registerTables();
		$this->registerResourceType('tables', 'forms:form', []);
		$this->registerResourceType('tables', self::TABLE, []);
		$this->logger->expects($this->exactly(2))->method('error');

		$this->assertSame([self::TABLE, self::ROW], array_keys($this->manager->getResourceTypes()));
		$this->assertSame('Resource type ' . self::ROW, $this->manager->getResourceType(self::ROW)?->getDisplayName());
		$this->assertNull($this->manager->getResourceType('forms:form'));
	}

	public function testCheckUnregisteredResourceType(): void {
		$this->registerTables();

		$this->expectException(\InvalidArgumentException::class);
		$this->manager->check($this->alice, new ResourceReference('forms:form', '1'));
	}

	public function testCheckSameResourceTwice(): void {
		$this->registerTables();

		$this->expectException(\InvalidArgumentException::class);
		$this->manager->checkAll($this->alice, [
			new ResourceAccess(new ResourceReference(self::TABLE, '1')),
			new ResourceAccess(new ResourceReference(self::TABLE, '1', Principal::user('bob'))),
		]);
	}

	public function testWithoutRestrictionsAllApplicablePermissionsAreAllowed(): void {
		$this->registerTables();
		$this->registerResourceType('tables', 'tables:view', [CorePermission::Read->value, 'forms:results']);

		$this->assertAllowed(
			[CorePermission::Read->value, CorePermission::DOWNLOAD, CorePermission::Update->value, CorePermission::Share->value, self::EXPORT],
			new ResourceReference(self::TABLE, '1'),
		);
		$this->assertAllowed([CorePermission::Read->value], new ResourceReference('tables:view', '1'));
	}

	public function testEntriesAreMerged(): void {
		$this->registerTables();
		$this->logger->expects($this->once())->method('warning');

		$this->assertAllowed(
			[CorePermission::Read->value, CorePermission::Update->value, CorePermission::Share->value],
			new ResourceReference(self::TABLE, '1'),
			[
				new AccessEntry(AccessEntry::RECIPIENT_GROUP, 'staff', [CorePermission::Read->value, CorePermission::Share->value]),
				// Delete does not apply to tables
				new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Read->value, CorePermission::Update->value, CorePermission::Delete->value]),
			],
		);
	}

	public function testResourceRestriction(): void {
		$this->registerTables();
		$this->registerRestrictionProvider(static fn (Actor $actor, array $accesses): array => [
			Restriction::forResource($accesses[0]->resource, [CorePermission::Update->value]),
		]);

		$resource = new ResourceReference(self::TABLE, '1');
		$this->assertAllowed([CorePermission::Read->value, CorePermission::DOWNLOAD, CorePermission::Share->value, self::EXPORT], $resource);
		$this->assertAllowed([CorePermission::Read->value], $resource, [
			new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Read->value, CorePermission::Update->value]),
		]);
	}

	public function testRestrictingCorePermissionRestrictsItsKinds(): void {
		$this->registerTables();
		$this->registerRestrictionProvider(static fn (Actor $actor, array $accesses): array => [
			Restriction::forResource($accesses[0]->resource, [CorePermission::Read->value, CorePermission::Share->value, 'forms:unknown']),
		]);

		// Read, download as a kind of read, share and export as a kind of share are restricted
		$this->assertAllowed([CorePermission::Update->value], new ResourceReference(self::TABLE, '1'));
	}

	public function testRestrictingKindDoesNotRestrictCorePermission(): void {
		$this->registerTables();
		$this->registerRestrictionProvider(static fn (Actor $actor, array $accesses): array => [
			Restriction::forResource($accesses[0]->resource, [CorePermission::DOWNLOAD]),
		]);

		$this->assertAllowed(
			[CorePermission::Read->value, CorePermission::Update->value, CorePermission::Share->value, self::EXPORT],
			new ResourceReference(self::TABLE, '1'),
		);
	}

	/**
	 * Alice is a visitor of the team sales, which only allows to read.
	 * The table is shared with the team and with her directly, the direct share is not restricted.
	 */
	public function testRecipientRestrictionOnlyAppliesToEntriesOfTheRecipient(): void {
		$this->registerTables();
		$onlyRead = array_values(array_filter(
			array_column(CorePermission::cases(), 'value'),
			static fn (string $id): bool => $id !== CorePermission::Read->value,
		));
		$this->registerRestrictionProvider(static fn (Actor $actor, array $accesses): array => [
			Restriction::forRecipient($accesses[0]->resource, AccessEntry::RECIPIENT_TEAM, 'sales', $onlyRead),
		]);
		$this->registerRestrictionProvider(static fn (Actor $actor, array $accesses): array => [
			Restriction::forResource($accesses[0]->resource, [CorePermission::DOWNLOAD]),
		]);

		$resource = new ResourceReference(self::TABLE, '1', Principal::user('bob'));
		$team = new AccessEntry(AccessEntry::RECIPIENT_TEAM, 'sales', [CorePermission::Read->value, CorePermission::DOWNLOAD, CorePermission::Update->value, self::EXPORT]);
		$direct = new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Read->value, CorePermission::Update->value]);

		$this->assertAllowed([CorePermission::Read->value], $resource, [$team]);
		$this->assertAllowed([CorePermission::Read->value, CorePermission::Update->value], $resource, [$team, $direct]);
		// The access returned by the resource type is a direct one
		$this->assertAllowed([CorePermission::Read->value, CorePermission::Update->value, CorePermission::Share->value, self::EXPORT], $resource);
	}

	public function testRestrictionsOfOtherResourcesAreIgnored(): void {
		$this->registerTables();
		$this->registerRestrictionProvider(static fn (): array => [
			Restriction::forResource(new ResourceReference(self::ROW, '2'), [CorePermission::Read->value]),
		]);

		$this->assertAllowed(
			[CorePermission::Read->value, CorePermission::Update->value, CorePermission::Delete->value],
			new ResourceReference(self::ROW, '1'),
		);
	}

	public function testFailingProviderRestrictsEverythingAndIsNotCached(): void {
		$this->registerTables();
		$fail = true;
		$this->registerRestrictionProvider(static function () use (&$fail): array {
			if ($fail) {
				throw new \RuntimeException('Label storage not available');
			}
			return [];
		});
		$this->logger->expects($this->once())->method('error');

		$table = new ResourceReference(self::TABLE, '1');
		$row = new ResourceReference(self::ROW, '1');
		$results = $this->manager->checkAll($this->alice, [new ResourceAccess($table), new ResourceAccess($row)]);
		$this->assertSame([], $results[$table->key()]->getAllowedPermissionIds());
		$this->assertSame([], $results[$row->key()]->getAllowedPermissionIds());
		$this->assertFalse($results[$row->key()]->isAllowed(CorePermission::Read->value));

		$fail = false;
		$this->assertAllowed([CorePermission::Read->value, CorePermission::Update->value, CorePermission::Delete->value], $row);
	}

	public function testProviderThatCannotBeLoadedRestrictsEverything(): void {
		$this->registerTables();
		$this->context->registerResourceRestrictionProvider('governance', 'label-provider');
		$this->logger->expects($this->once())->method('error');

		$row = new ResourceReference(self::ROW, '1');
		$this->assertAllowed([], $row);

		$this->instances['label-provider'] = new class implements IRestrictionProvider {
			public function getRestrictions(Actor $actor, array $accesses): iterable {
				return [Restriction::forResource($accesses[0]->resource, [CorePermission::Delete->value])];
			}
		};
		$this->assertAllowed([CorePermission::Read->value, CorePermission::Update->value], $row);
	}

	public function testRegistrationThroughAppContext(): void {
		$this->registerTables();
		$this->instances['forms-type'] = new class implements IResourceType {
			public function getId(): string {
				return 'forms:form';
			}

			public function getDisplayName(): string {
				return 'Form';
			}

			public function getPermissionIds(): array {
				return [CorePermission::Read->value, 'forms:results'];
			}

			public function getAccess(Actor $actor, array $resources): array {
				return [$resources[0]->key() => [new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', $this->getPermissionIds())]];
			}
		};
		$this->instances['forms-permission'] = new class extends APermission {
			public function getId(): string {
				return 'forms:results';
			}

			public function getDisplayName(): string {
				return 'See results';
			}

			public function getKindOf(): CorePermission {
				return CorePermission::Read;
			}
		};
		$this->instances['forms-provider'] = new class implements IRestrictionProvider {
			public function getRestrictions(Actor $actor, array $accesses): iterable {
				return [Restriction::forResource($accesses[0]->resource, ['forms:results'])];
			}
		};

		$context = $this->context->for('forms');
		$context->registerResourceType('forms-type');
		$context->registerResourcePermission('forms-permission');
		$context->registerResourceRestrictionProvider('forms-provider');

		$this->assertAllowed([CorePermission::Read->value], new ResourceReference('forms:form', '1'));
	}

	public function testCheckAll(): void {
		$this->registerTables();
		$this->registerRestrictionProvider(static function (Actor $actor, array $accesses): \Generator {
			foreach ($accesses as $access) {
				if ($access->resource->type === self::ROW && $access->resource->id !== '1') {
					yield Restriction::forResource($access->resource, [CorePermission::Delete->value]);
				}
			}
		});

		$accesses = [
			new ResourceAccess(new ResourceReference(self::TABLE, '1')),
			new ResourceAccess(new ResourceReference(self::ROW, '1')),
			new ResourceAccess(new ResourceReference(self::ROW, '2'), [
				new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Read->value, CorePermission::Delete->value]),
			]),
		];
		$results = $this->manager->checkAll($this->alice, $accesses);

		$this->assertSame(['tables:table/1', 'tables:row/1', 'tables:row/2'], array_keys($results));
		$this->assertTrue($results['tables:table/1']->isAllowed(self::EXPORT));
		$this->assertTrue($results['tables:row/1']->isAllowed(CorePermission::Delete->value));
		$this->assertSame([CorePermission::Read->value], $results['tables:row/2']->getAllowedPermissionIds());
		foreach ($accesses as $access) {
			$this->assertEquals($results[$access->resource->key()], $this->manager->check($this->alice, $access->resource, $access->entries));
		}
	}

	public function testResultsAreCachedPerActorResourceAndEntries(): void {
		$this->registerTables();
		$provider = $this->registerRestrictionProvider(static fn (): array => []);
		$row1 = new ResourceReference(self::ROW, '1');
		$row2 = new ResourceReference(self::ROW, '2');
		$entries = [new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Read->value])];

		$this->manager->check($this->alice, $row1);
		$this->manager->check($this->alice, $row1);
		$this->assertCount(1, $provider->calls);

		// Only the resource that was not checked yet is passed to the provider
		$this->manager->checkAll($this->alice, [new ResourceAccess($row1), new ResourceAccess($row2)]);
		$this->assertCount(2, $provider->calls);
		$this->assertEquals([new ResourceAccess($row2, [
			new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Read->value, CorePermission::Update->value, CorePermission::Delete->value]),
		])], $provider->calls[1]);

		$this->manager->check($this->alice, $row1, $entries);
		$this->manager->check($this->alice, $row1, $entries);
		$this->assertCount(3, $provider->calls);

		$this->manager->check(Actor::of(Principal::user('bob')), $row1);
		$this->manager->check(Actor::of(Principal::bot('summarizer', 'assistant'))->onBehalfOf(Principal::user('alice')), $row1);
		$this->assertCount(5, $provider->calls);

		$this->manager->clearCache();
		$this->manager->check($this->alice, $row1);
		$this->assertCount(6, $provider->calls);
	}

	public function testResourceTypeIsAskedOncePerTypeForAccessesWithoutEntries(): void {
		$this->registerCorePermissions();
		$share = static fn (string $key): array => [$key => [new AccessEntry(AccessEntry::RECIPIENT_GROUP, 'staff', [CorePermission::Read->value])]];
		$tables = $this->registerResourceType('tables', self::TABLE, [CorePermission::Read->value, CorePermission::Update->value], static fn (): array => $share('tables:table/1'));
		$rows = $this->registerResourceType('tables', self::ROW, [CorePermission::Read->value], static fn (): array => $share('tables:row/1'));

		$table1 = new ResourceReference(self::TABLE, '1');
		$table2 = new ResourceReference(self::TABLE, '2');
		$table3 = new ResourceReference(self::TABLE, '3');
		$row1 = new ResourceReference(self::ROW, '1');
		$results = $this->manager->checkAll($this->alice, [
			new ResourceAccess($table1),
			new ResourceAccess($row1),
			new ResourceAccess($table2),
			new ResourceAccess($table3, [new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Update->value])]),
		]);

		$this->assertSame(['tables:table/1', 'tables:row/1', 'tables:table/2', 'tables:table/3'], array_keys($results));
		$this->assertSame([CorePermission::Read->value], $results['tables:table/1']->getAllowedPermissionIds());
		$this->assertSame([CorePermission::Read->value], $results['tables:row/1']->getAllowedPermissionIds());
		// Left out by the resource type
		$this->assertSame([], $results['tables:table/2']->getAllowedPermissionIds());
		$this->assertSame([CorePermission::Update->value], $results['tables:table/3']->getAllowedPermissionIds());
		$this->assertEquals([[$table1, $table2]], $tables->calls);
		$this->assertEquals([[$row1]], $rows->calls);
	}

	public function testWithoutAccessNothingIsAllowedAndProvidersAreNotAsked(): void {
		$this->registerTables();
		$provider = $this->registerRestrictionProvider(static fn (): array => []);

		$this->assertAllowed([], new ResourceReference(self::TABLE, '1'), []);
		$this->assertSame([], $provider->calls);
	}

	public function testAccessOfTheResourceTypeIsCached(): void {
		$this->registerCorePermissions();
		$rows = $this->registerResourceType('tables', self::ROW, [CorePermission::Read->value]);
		$provider = $this->registerRestrictionProvider(static fn (): array => []);
		$row = new ResourceReference(self::ROW, '1');

		$this->manager->check($this->alice, $row);
		// The result is shared with a check that passes the same entries
		$this->manager->check($this->alice, $row, [new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Read->value])]);
		$this->manager->check($this->alice, $row);
		$this->assertCount(1, $rows->calls);
		$this->assertCount(1, $provider->calls);

		$this->manager->clearCache();
		$this->manager->check($this->alice, $row);
		$this->assertCount(2, $rows->calls);
	}

	public function testFailingResourceTypeAllowsNothingAndIsNotCached(): void {
		$this->registerCorePermissions();
		$fail = true;
		$this->registerResourceType('tables', self::ROW, [CorePermission::Read->value], static function (Actor $actor, array $resources) use (&$fail): array {
			if ($fail) {
				throw new \RuntimeException('Share storage not available');
			}
			return ['tables:row/1' => [new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Read->value])]];
		});
		$this->registerResourceType('tables', self::TABLE, [CorePermission::Read->value]);
		$this->logger->expects($this->once())->method('error');

		$row = new ResourceReference(self::ROW, '1');
		$table = new ResourceReference(self::TABLE, '1');
		$results = $this->manager->checkAll($this->alice, [new ResourceAccess($row), new ResourceAccess(new ResourceReference(self::ROW, '2')), new ResourceAccess($table)]);
		$this->assertSame([], $results[$row->key()]->getAllowedPermissionIds());
		$this->assertSame([CorePermission::Read->value], $results[$table->key()]->getAllowedPermissionIds());

		$fail = false;
		$this->assertAllowed([CorePermission::Read->value], $row);
	}

	public function testGetAccess(): void {
		$this->registerCorePermissions();
		$entry = new AccessEntry(AccessEntry::RECIPIENT_TEAM, 'sales', [CorePermission::Read->value]);
		$fail = true;
		$this->registerResourceType('tables', self::TABLE, [CorePermission::Read->value], static fn (): array => ['tables:table/1' => [$entry], 'tables:table/3' => []]);
		$this->registerResourceType('tables', self::ROW, [CorePermission::Read->value], static function () use (&$fail, $entry): array {
			if ($fail) {
				throw new \RuntimeException('Share storage not available');
			}
			return ['tables:row/1' => [$entry]];
		});
		$this->logger->expects($this->once())->method('error');

		$resources = [
			new ResourceReference(self::TABLE, '1'),
			new ResourceReference(self::TABLE, '2'),
			new ResourceReference(self::TABLE, '3'),
			new ResourceReference(self::ROW, '1'),
		];
		$this->assertSame(['tables:table/1' => [$entry]], $this->accessManager->getAccess($this->alice, $resources));

		$fail = false;
		$this->assertSame(['tables:table/1' => [$entry], 'tables:row/1' => [$entry]], $this->accessManager->getAccess($this->alice, $resources));
	}

	public function testGetAccessForUnregisteredResourceType(): void {
		$this->assertInstanceOf(AccessManager::class, Server::get(IAccessManager::class));

		$this->expectException(\InvalidArgumentException::class);
		$this->accessManager->getAccess($this->alice, [new ResourceReference('forms:form', '1')]);
	}

	public function testApplicablePermissionsAreReadOncePerResourceType(): void {
		$this->registerCorePermissions();
		$resourceType = $this->createMock(IResourceType::class);
		$resourceType->method('getId')->willReturn(self::ROW);
		$resourceType->expects($this->once())->method('getPermissionIds')->willReturn([CorePermission::Read->value]);
		$resourceType->method('getAccess')->willReturn([
			'tables:row/1' => [new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Read->value])],
			'tables:row/2' => [new AccessEntry(AccessEntry::RECIPIENT_USER, 'alice', [CorePermission::Read->value])],
		]);
		$this->instances[self::ROW] = $resourceType;
		$this->context->registerResourceType('tables', self::ROW);

		$results = $this->manager->checkAll($this->alice, [
			new ResourceAccess(new ResourceReference(self::ROW, '1')),
			new ResourceAccess(new ResourceReference(self::ROW, '2')),
		]);

		$this->assertSame([CorePermission::Read->value], $results['tables:row/2']->getAllowedPermissionIds());
	}
}

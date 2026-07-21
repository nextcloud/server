<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Tests\AppFramework\ORM;

use OC\AppFramework\ORM\EntityManager;
use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\AppFramework\ORM\Attribute\JoinColumn;
use OCP\AppFramework\ORM\Attribute\OneToOne;
use OCP\AppFramework\ORM\Repository;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

#[Entity(name: 'repository_test_test1')]
class NoPrimaryKey {
	#[Column(name: 'id', type: Types::INTEGER, nullable: true)]
	public ?int $id = null;
}

/** @template-extends Repository<NoPrimaryKey> */
class NoPrimaryKeyRepository extends Repository {
	public function __construct(
		IDBConnection $connection,
	) {
		parent::__construct($connection, NoPrimaryKey::class);
	}
}

#[Entity(name: 'repository_test_test2')]
class PrimaryKey {
	#[Id]
	#[Column(name: 'id', type: Types::INTEGER, nullable: false)]
	public ?int $id = null;

	#[Column(name: 'name', type: Types::STRING, nullable: true)]
	public ?string $name = null;

	#[Column(name: 'notNullable', type: Types::STRING, nullable: false)]
	public string $notNullable;

	#[Column(name: 'integer_val', type: Types::INTEGER, nullable: false)]
	public int $integer;

	#[Column(name: 'bigInt_val', type: Types::BIGINT, nullable: false)]
	public int $bigInt;

	#[Column(name: 'float_val', type: Types::FLOAT, nullable: false)]
	public float $float;

	#[Column(name: 'date_val', type: Types::DATETIME, nullable: false)]
	public \DateTime $date;
}

/** @template-extends Repository<PrimaryKey> */
class PrimaryKeyRepository extends Repository {
	public function __construct(
		IDBConnection $connection,
	) {
		parent::__construct($connection, PrimaryKey::class);
	}
}

#[Entity(name: 'repository_customer')]
 final class Customer {
    #[Id]
    #[Column(name: 'id', type: Types::BIGINT)]
    public ?int $id = null;

    #[OneToOne(targetEntity: Cart::class, mappedBy: 'customer')]
    #[JoinColumn(name: 'cart_id', referencedColumnName: 'id')]
    public Cart|null $cart = null;

	#[Column(name: 'name', type: Types::STRING, nullable: false)]
	public string $name;
}

/** @template-extends Repository<Customer> */
class CustomerRepository extends Repository {
	public function __construct(
		IDBConnection $connection,
	) {
		parent::__construct($connection, Customer::class);
	}
}

 #[Entity(name: 'repository_cart')]
 final class Cart {
    #[Id]
    #[Column(name: 'id', type: Types::BIGINT)]
    public ?int $id = null;

    #[OneToOne(targetEntity: Customer::class, invertedBy: 'cart')]
    #[JoinColumn(name: 'customer_id', referencedColumnName: 'id')]
    public Customer|null $customer;
}

/** @template-extends Repository<Cart> */
class CartRepository extends Repository {
	public function __construct(
		IDBConnection $connection,
	) {
		parent::__construct($connection, Cart::class);
	}
}

#[Entity(name: 'repository_invalid_owner')]
final class InvalidOwningOnDelete {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: InvalidMappedByOnDelete::class, invertedBy: 'owner')]
	#[JoinColumn(name: 'invalid_id', referencedColumnName: 'id')]
	public InvalidMappedByOnDelete|null $invalid = null;
}

#[Entity(name: 'repository_invalid_mapped')]
final class InvalidMappedByOnDelete {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: InvalidOwningOnDelete::class, mappedBy: 'invalid')]
	#[JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
	public InvalidOwningOnDelete|null $owner = null;
}

#[Entity(name: 'repository_typo_owner')]
final class TypoOwning {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: TypoTarget::class, invertedBy: 'owner')]
	#[JoinColumn(name: 'target_id', referencedColumnName: 'id')]
	public TypoTarget|null $target = null;
}

#[Entity(name: 'repository_typo_target')]
final class TypoTarget {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: TypoOwning::class, mappedBy: 'ownerTypo')]
	#[JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
	public TypoOwning|null $owner = null;
}

#[Entity(name: 'repository_cascade_parent')]
final class CascadeParent {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[Column(name: 'name', type: Types::STRING, nullable: true)]
	public ?string $name = null;

	#[OneToOne(targetEntity: CascadeChild::class, mappedBy: 'parent')]
	#[JoinColumn(name: 'child_id', referencedColumnName: 'id')]
	public CascadeChild|null $child = null;
}

/** @template-extends Repository<CascadeParent> */
class CascadeParentRepository extends Repository {
	public function __construct(
		IDBConnection $connection,
	) {
		parent::__construct($connection, CascadeParent::class);
	}
}

#[Entity(name: 'repository_cascade_child')]
final class CascadeChild {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: CascadeParent::class, invertedBy: 'child')]
	#[JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
	public CascadeParent|null $parent = null;
}

/** @template-extends Repository<CascadeChild> */
class CascadeChildRepository extends Repository {
	public function __construct(
		IDBConnection $connection,
	) {
		parent::__construct($connection, CascadeChild::class);
	}
}

#[\PHPUnit\Framework\Attributes\Group('DB')]
class RepositoryTest extends TestCase {
	/** @var array<class-string, class-string<Repository>> */
	public static array $entitiesClasses = [
		NoPrimaryKey::class => NoPrimaryKeyRepository::class,
		PrimaryKey::class => PrimaryKeyRepository::class,
		Customer::class => CustomerRepository::class,
		Cart::class => CartRepository::class,
		CascadeParent::class => CascadeParentRepository::class,
		CascadeChild::class => CascadeChildRepository::class,
	];

	public static function setUpBeforeClass(): void {
		$schema = new SchemaWrapper(Server::get(Connection::class));
		$entityManager = Server::get(EntityManager::class);
		foreach (static::$entitiesClasses as $entityClass => $repositoryClass) {
			try {
				$entityManager->createTable($entityClass, $schema);
			} catch (\RuntimeException) {
				self::assertEquals(NoPrimaryKey::class, $entityClass);
			}
		}
		Server::get(Connection::class)->migrateToSchema($schema->getWrappedSchema());
	}

	public static function tearDownAfterClass(): void {
		$entityManager = Server::get(EntityManager::class);
		$prefix = Server::get(IConfig::class)->getSystemValueString('dbtableprefix', 'oc_');
		foreach (static::$entitiesClasses as $entityClass => $repositoryClass) {
			try {
				$entityManager->dropTable($entityClass, $prefix);
			} catch (\RuntimeException) {
				self::assertEquals(NoPrimaryKey::class, $entityClass);
			}
		}
	}

	public function testMissingPrimaryKey(): void {
		$this->expectException(\RuntimeException::class);

		$repo = Server::get(NoPrimaryKeyRepository::class);
		$_ = $repo->getTableName();
	}

	public function testPrimaryKey(): void {
		$repo = Server::get(PrimaryKeyRepository::class);
		$this->assertEquals('repository_test_test2', $repo->getTableName());
		$entity = new PrimaryKey();
		$entity->name = null;
		$entity->notNullable = 'ab';
		$entity->integer = 5;
		$entity->bigInt = 5;
		$entity->float = 3.0;
		$entity->date = new \DateTime('now');

		$repo->insert($entity);
		$this->assertNotNull($entity->id);

		// Confirm only one entry in the DB
		$entities = iterator_to_array($repo->yieldAll());
		$this->assertCount(1, $entities);
		$savedEntity = $entities[0];
		$this->assertEquals($entity->name, $savedEntity->name);
		$this->assertEquals($entity->notNullable, $savedEntity->notNullable);
		$this->assertEquals($entity->integer, $savedEntity->integer);
		$this->assertEquals($entity->bigInt, $savedEntity->bigInt);
		$this->assertEquals($entity->float, $savedEntity->float);
		$this->assertEquals($entity->date->getTimestamp(), $savedEntity->date->getTimestamp());

		// Search by primary key
		$savedEntity = $repo->findOneBy(['id' => $entity->id]);
		$this->assertEquals($entity->name, $savedEntity->name);
		$this->assertEquals($entity->notNullable, $savedEntity->notNullable);
		$this->assertEquals($entity->integer, $savedEntity->integer);
		$this->assertEquals($entity->bigInt, $savedEntity->bigInt);
		$this->assertEquals($entity->float, $savedEntity->float);
		$this->assertEquals($entity->date->getTimestamp(), $savedEntity->date->getTimestamp());

		// Search by null
		$savedEntity = $repo->findOneBy(['name' => $entity->name]);
		$this->assertEquals($entity->name, $savedEntity->name);
		$this->assertEquals($entity->notNullable, $savedEntity->notNullable);
		$this->assertEquals($entity->integer, $savedEntity->integer);
		$this->assertEquals($entity->bigInt, $savedEntity->bigInt);
		$this->assertEquals($entity->float, $savedEntity->float);
		$this->assertEquals($entity->date->getTimestamp(), $savedEntity->date->getTimestamp());

		// Search by other fields
		$savedEntity = $repo->findOneBy([
			'notNullable' => $entity->notNullable,
			'integer' => $entity->integer,
			'bigInt' => $entity->bigInt,
		]);
		/** @psalm-assert PrimaryKey $savedEntity */
		$this->assertEquals($entity->name, $savedEntity->name);
		$this->assertEquals($entity->notNullable, $savedEntity->notNullable);
		$this->assertEquals($entity->integer, $savedEntity->integer);
		$this->assertEquals($entity->bigInt, $savedEntity->bigInt);
		$this->assertEquals($entity->float, $savedEntity->float);
		$this->assertEquals($entity->date->getTimestamp(), $savedEntity->date->getTimestamp());

		// update
		$entity->name = 'not null anymore';
		$repo->update($entity);

		$savedEntity = $repo->findOneBy(['id' => $entity->id]);
		$this->assertEquals($entity->name, $savedEntity->name);

		// delete
		$repo->delete($savedEntity);
	}

	public function testDeleteBy(): void {
		$repo = Server::get(PrimaryKeyRepository::class);

		$entity = new PrimaryKey();
		$entity->name = null;
		$entity->notNullable = 'ab';
		$entity->integer = 5;
		$entity->bigInt = 5;
		$entity->float = 3.0;
		$entity->date = new \DateTime('now');

		$repo->insert($entity);
		$this->assertNotNull($entity->id);

		// Confirm only one entry in the DB
		$entities = iterator_to_array($repo->yieldAll());
		$this->assertCount(1, $entities);

		$repo->deleteBy(['id' => $entity->id]);

		$entities = iterator_to_array($repo->yieldAll());
		$this->assertCount(0, $entities);
	}

	public function testOneToOne(): void {
		$cartRepo = Server::get(CartRepository::class);
		$customerRepo = Server::get(CustomerRepository::class);

		$customer = new Customer();
		$customer->name = 'foo';
		$customerRepo->insert($customer);
		$this->assertNotNull($customer->id);

		$savedCustomer = $customerRepo->findOneBy(['id' => $customer->id]);
		$this->assertNull($savedCustomer->cart);

		$cart = new Cart();
		$cart->customer = $customer;
		$cartRepo->insert($cart);
		$this->assertNotNull($cart->id);

		// Owning side: reading the cart back resolves the customer relation
		$savedCart = $cartRepo->findOneBy(['id' => $cart->id]);
		$this->assertNotNull($savedCart->customer);
		$this->assertEquals($customer->id, $savedCart->customer->id);
		$this->assertEquals($customer->name, $savedCart->customer->name);

		// Inverse side: reading the customer back resolves the cart relation
		$savedCustomer = $customerRepo->findOneBy(['id' => $customer->id]);
		$this->assertNotNull($savedCustomer->cart);
		$this->assertEquals($cart->id, $savedCustomer->cart->id);
	}

	public function testInsertOnMappedBySideThrows(): void {
		$customerRepo = Server::get(CustomerRepository::class);

		$cart = new Cart();
		$cart->customer = null;

		$customer = new Customer();
		$customer->name = 'foo';
		$customer->cart = $cart;

		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('is the mappedBy (inverse) side of a OneToOne relation');

		$customerRepo->insert($customer);
	}

	public function testOneToOneUniqueConstraint(): void {
		$cartRepo = Server::get(CartRepository::class);
		$customerRepo = Server::get(CustomerRepository::class);

		$customer = new Customer();
		$customer->name = 'shared';
		$customerRepo->insert($customer);

		$cart1 = new Cart();
		$cart1->customer = $customer;
		$cartRepo->insert($cart1);

		$cart2 = new Cart();
		$cart2->customer = $customer;

		try {
			$cartRepo->insert($cart2);
			$this->fail('Expected inserting a second Cart for the same Customer to violate the unique constraint.');
		} catch (\OCP\DB\Exception $e) {
			$this->assertEquals(\OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION, $e->getReason());
		}
	}

	public function testOneToOneNullRelation(): void {
		$customerRepo = Server::get(CustomerRepository::class);

		$customer = new Customer();
		$customer->name = 'no cart yet';
		$customerRepo->insert($customer);

		$savedCustomer = $customerRepo->findOneBy(['id' => $customer->id]);
		$this->assertNull($savedCustomer->cart);
	}

	public function testMappedByOnDeleteThrows(): void {
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('sets JoinColumn::$onDelete on the mappedBy (inverse) side');

		Server::get(EntityManager::class)->getEntityInfo(InvalidMappedByOnDelete::class);
	}

	public function testTypoInMappedByThrows(): void {
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('has no property named \'ownerTypo\'');

		Server::get(EntityManager::class)->getEntityInfo(TypoTarget::class);
	}

	public function testDeleteWithDanglingMappedByThrows(): void {
		$cartRepo = Server::get(CartRepository::class);
		$customerRepo = Server::get(CustomerRepository::class);

		$customer = new Customer();
		$customer->name = 'has a cart';
		$customerRepo->insert($customer);

		$cart = new Cart();
		$cart->customer = $customer;
		$cartRepo->insert($cart);

		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('still references it');

		$customerRepo->delete($customer);
	}

	public function testDeleteCascadesWhenConfigured(): void {
		$parentRepo = Server::get(CascadeParentRepository::class);
		$childRepo = Server::get(CascadeChildRepository::class);

		$parent = new CascadeParent();
		$parentRepo->insert($parent);

		$child = new CascadeChild();
		$child->parent = $parent;
		$childRepo->insert($child);

		$parentRepo->delete($parent);

		$entities = iterator_to_array($childRepo->yieldAll());
		$this->assertCount(0, $entities);
	}

	private function normalizeSql(string $sql): string {
		return str_replace(['`', '"'], '', $sql);
	}

	public function testOwningSideGeneratesLeftJoin(): void {
		$cartRepo = Server::get(CartRepository::class);

		/** @var array{0: IQueryBuilder, 1: array} $result */
		$result = self::invokePrivate($cartRepo, 'getJoinedSelectQueryBuilder', [['id' => 1], []]);
		[$qb, $relations] = $result;

		$this->assertCount(1, $relations);
		$this->assertEquals(
			'SELECT e.id AS e_id, r0.id AS r0_id, r0.name AS r0_name '
				. 'FROM *PREFIX*repository_cart e '
				. 'LEFT JOIN *PREFIX*repository_customer r0 ON e.customer_id = r0.id '
				. 'WHERE e.id = :dcValue1',
			$this->normalizeSql($qb->getSQL()),
		);
	}

	public function testInverseSideGeneratesLeftJoin(): void {
		$customerRepo = Server::get(CustomerRepository::class);

		/** @var array{0: IQueryBuilder, 1: array} $result */
		$result = self::invokePrivate($customerRepo, 'getJoinedSelectQueryBuilder', [['id' => 1], []]);
		[$qb, $relations] = $result;

		$this->assertCount(1, $relations);
		$this->assertEquals(
			'SELECT e.id AS e_id, e.name AS e_name, r0.id AS r0_id '
				. 'FROM *PREFIX*repository_customer e '
				. 'LEFT JOIN *PREFIX*repository_cart r0 ON r0.customer_id = e.id '
				. 'WHERE e.id = :dcValue1',
			$this->normalizeSql($qb->getSQL()),
		);
	}

	public function testEntityWithoutRelationsGeneratesNoJoin(): void {
		$repo = Server::get(PrimaryKeyRepository::class);

		/** @var array{0: IQueryBuilder, 1: array} $result */
		$result = self::invokePrivate($repo, 'getJoinedSelectQueryBuilder', [['id' => 1], []]);
		[$qb, $relations] = $result;

		$this->assertCount(0, $relations);
		$this->assertEquals(
			'SELECT e.id AS e_id, e.name AS e_name, e.notNullable AS e_notNullable, '
				. 'e.integer_val AS e_integer_val, e.bigInt_val AS e_bigInt_val, '
				. 'e.float_val AS e_float_val, e.date_val AS e_date_val '
				. 'FROM *PREFIX*repository_test_test2 e '
				. 'WHERE e.id = :dcValue1',
			$this->normalizeSql($qb->getSQL()),
		);
	}
}

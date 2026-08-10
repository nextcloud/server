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
use OCP\AppFramework\ORM\Attribute\ManyToOne;
use OCP\AppFramework\ORM\Attribute\OneToOne;
use OCP\AppFramework\ORM\Repository;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IConfig;
use OCP\Server;
use Test\TestCase;

#[Entity(name: 'repository_test_test1')]
class NoPrimaryKey {
	#[Column(name: 'id', type: Types::INTEGER, nullable: true)]
	public ?int $id = null;
}

#[Entity(name: 'repository_test_test2')]
class PrimaryKey {
	#[Id]
	#[Column(name: 'id', type: Types::INTEGER, nullable: false)]
	public ?int $id = null;

	#[Column(name: 'name', type: Types::STRING, nullable: true)]
	public ?string $name = null;

	#[Column(name: 'not_nullable', type: Types::STRING, nullable: false)]
	public string $notNullable;

	#[Column(name: 'integer_val', type: Types::INTEGER, nullable: false)]
	public int $integer;

	#[Column(name: 'bigint_val', type: Types::BIGINT, nullable: false)]
	public int $bigInt;

	#[Column(name: 'float_val', type: Types::FLOAT, nullable: false)]
	public float $float;

	#[Column(name: 'date_val', type: Types::DATETIME, nullable: false)]
	public \DateTime $date;
}

#[Entity(name: 'repository_composite_key')]
final class CompositeKey {
	#[Id]
	#[Column(name: 'tenant_id', type: Types::BIGINT)]
	public ?int $tenantId = null;

	#[Id]
	#[Column(name: 'item_id', type: Types::BIGINT)]
	public ?int $itemId = null;

	#[Column(name: 'label', type: Types::STRING, nullable: true)]
	public ?string $label = null;
}

#[Entity(name: 'repository_customer')]
final class Customer {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: Cart::class, mappedBy: 'customer')]
	#[JoinColumn(name: 'cart_id', referencedColumnName: 'id')]
	public ?Cart $cart = null;

	#[Column(name: 'name', type: Types::STRING, nullable: false)]
	public string $name;
}

#[Entity(name: 'repository_cart')]
final class Cart {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: Customer::class, invertedBy: 'cart')]
	#[JoinColumn(name: 'customer_id', referencedColumnName: 'id')]
	public ?Customer $customer;
}

#[Entity(name: 'repository_invalid_owner')]
final class InvalidOwningOnDelete {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: InvalidMappedByOnDelete::class, invertedBy: 'owner')]
	#[JoinColumn(name: 'invalid_id', referencedColumnName: 'id')]
	public ?InvalidMappedByOnDelete $invalid = null;
}

#[Entity(name: 'repository_invalid_mapped')]
final class InvalidMappedByOnDelete {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: InvalidOwningOnDelete::class, mappedBy: 'invalid')]
	#[JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
	public ?InvalidOwningOnDelete $owner = null;
}

#[Entity(name: 'repository_typo_owner')]
final class TypoOwning {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: TypoTarget::class, invertedBy: 'owner')]
	#[JoinColumn(name: 'target_id', referencedColumnName: 'id')]
	public ?TypoTarget $target = null;
}

#[Entity(name: 'repository_typo_target')]
final class TypoTarget {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: TypoOwning::class, mappedBy: 'ownerTypo')]
	#[JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
	public ?TypoOwning $owner = null;
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
	public ?CascadeChild $child = null;
}

#[Entity(name: 'repository_cascade_child')]
final class CascadeChild {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[OneToOne(targetEntity: CascadeParent::class, invertedBy: 'child')]
	#[JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
	public ?CascadeParent $parent = null;
}

#[Entity(name: 'repository_merchant')]
final class Merchant {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[Column(name: 'name', type: Types::STRING, nullable: true)]
	public ?string $name = null;
}

#[Entity(name: 'repository_order')]
final class Order {
	#[Id]
	#[Column(name: 'id', type: Types::BIGINT)]
	public ?int $id = null;

	#[ManyToOne(targetEntity: Merchant::class)]
	#[JoinColumn(name: 'merchant_id', referencedColumnName: 'id', nullable: true)]
	public ?Merchant $merchant = null;
}

#[\PHPUnit\Framework\Attributes\Group('DB')]
class RepositoryTest extends TestCase {
	/** @var list<class-string> */
	public static array $entitiesClasses = [
		NoPrimaryKey::class,
		PrimaryKey::class,
		CompositeKey::class,
		Customer::class,
		Cart::class,
		CascadeParent::class,
		CascadeChild::class,
		Merchant::class,
		Order::class,
	];

	public static function setUpBeforeClass(): void {
		$schema = new SchemaWrapper(Server::get(Connection::class));
		$entityManager = Server::get(EntityManager::class);
		foreach (static::$entitiesClasses as $entityClass) {
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
		foreach (static::$entitiesClasses as $entityClass) {
			try {
				$entityManager->dropTable($entityClass, $prefix);
			} catch (\RuntimeException) {
				self::assertEquals(NoPrimaryKey::class, $entityClass);
			}
		}
	}

	/**
	 * @template T of object
	 * @param class-string<T> $entityClass
	 * @return Repository<T>
	 */
	private function getRepository(string $entityClass): Repository {
		return Server::get(EntityManager::class)->getRepository($entityClass);
	}

	public function testMissingPrimaryKey(): void {
		$this->expectException(\RuntimeException::class);

		$repo = $this->getRepository(NoPrimaryKey::class);
		$_ = $repo->getTableName();
	}

	public function testPrimaryKey(): void {
		$repo = $this->getRepository(PrimaryKey::class);
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
		$repo = $this->getRepository(PrimaryKey::class);

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

	public function testCompositePrimaryKey(): void {
		$repo = $this->getRepository(CompositeKey::class);
		$this->assertEquals('repository_composite_key', $repo->getTableName());

		$entity = new CompositeKey();
		$entity->tenantId = 1;
		$entity->itemId = 42;
		$entity->label = 'first';
		$repo->insert($entity);

		// A second entity sharing the tenantId but not the itemId is a distinct row.
		$other = new CompositeKey();
		$other->tenantId = 1;
		$other->itemId = 43;
		$other->label = 'second';
		$repo->insert($other);

		$savedEntity = $repo->findOneBy(['tenantId' => 1, 'itemId' => 42]);
		$this->assertEquals('first', $savedEntity->label);

		$savedOther = $repo->findOneBy(['tenantId' => 1, 'itemId' => 43]);
		$this->assertEquals('second', $savedOther->label);

		// update
		$entity->label = 'updated';
		$repo->update($entity);

		$savedEntity = $repo->findOneBy(['tenantId' => 1, 'itemId' => 42]);
		$this->assertEquals('updated', $savedEntity->label);
		// The other row with the same tenantId is untouched.
		$savedOther = $repo->findOneBy(['tenantId' => 1, 'itemId' => 43]);
		$this->assertEquals('second', $savedOther->label);

		// delete
		$repo->delete($entity);
		$this->assertCount(0, iterator_to_array($repo->findBy(['tenantId' => 1, 'itemId' => 42])));
		$this->assertCount(1, iterator_to_array($repo->findBy(['tenantId' => 1, 'itemId' => 43])));

		$repo->delete($savedOther);
	}

	public function testCompositePrimaryKeyMissingPartOnInsertThrows(): void {
		$repo = $this->getRepository(CompositeKey::class);

		$entity = new CompositeKey();
		$entity->tenantId = 2;
		// itemId intentionally left unset: composite keys can't rely on DB autoincrement.
		$entity->label = 'incomplete';

		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('is part of a composite primary key and must be set before insert()');

		$repo->insert($entity);
	}

	public function testCompositePrimaryKeyMissingPartOnUpdateThrows(): void {
		$repo = $this->getRepository(CompositeKey::class);

		$entity = new CompositeKey();
		$entity->tenantId = 3;
		$entity->itemId = 1;
		$entity->label = 'x';
		$repo->insert($entity);

		$entity->itemId = null;

		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Trying to update an entity with no primary key set.');

		try {
			$repo->update($entity);
		} finally {
			$entity->itemId = 1;
			$repo->delete($entity);
		}
	}

	public function testOneToOne(): void {
		$cartRepo = $this->getRepository(Cart::class);
		$customerRepo = $this->getRepository(Customer::class);

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
		$customerRepo = $this->getRepository(Customer::class);

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
		$cartRepo = $this->getRepository(Cart::class);
		$customerRepo = $this->getRepository(Customer::class);

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
		$customerRepo = $this->getRepository(Customer::class);

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
		$cartRepo = $this->getRepository(Cart::class);
		$customerRepo = $this->getRepository(Customer::class);

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
		$parentRepo = $this->getRepository(CascadeParent::class);
		$childRepo = $this->getRepository(CascadeChild::class);

		$parent = new CascadeParent();
		$parentRepo->insert($parent);

		$child = new CascadeChild();
		$child->parent = $parent;
		$childRepo->insert($child);

		$parentRepo->delete($parent);

		$entities = iterator_to_array($childRepo->yieldAll());
		$this->assertCount(0, $entities);
	}

	public function testManyToOne(): void {
		$merchantRepo = $this->getRepository(Merchant::class);
		$orderRepo = $this->getRepository(Order::class);

		$merchant = new Merchant();
		$merchant->name = 'Acme';
		$merchantRepo->insert($merchant);

		$order1 = new Order();
		$order1->merchant = $merchant;
		$orderRepo->insert($order1);

		// Unlike OneToOne, a second Order for the same Merchant must be allowed.
		$order2 = new Order();
		$order2->merchant = $merchant;
		$orderRepo->insert($order2);

		$savedOrder1 = $orderRepo->findOneBy(['id' => $order1->id]);
		$this->assertNotNull($savedOrder1->merchant);
		$this->assertEquals($merchant->id, $savedOrder1->merchant->id);
		$this->assertEquals($merchant->name, $savedOrder1->merchant->name);

		$savedOrder2 = $orderRepo->findOneBy(['id' => $order2->id]);
		$this->assertEquals($merchant->id, $savedOrder2->merchant->id);
	}

	public function testManyToOneNullRelation(): void {
		$orderRepo = $this->getRepository(Order::class);

		$order = new Order();
		$orderRepo->insert($order);

		$savedOrder = $orderRepo->findOneBy(['id' => $order->id]);
		$this->assertNull($savedOrder->merchant);
	}

	private function normalizeSql(string $sql): string {
		return str_replace(['`', '"'], '', $sql);
	}

	public function testOwningSideGeneratesLeftJoin(): void {
		$cartRepo = $this->getRepository(Cart::class);

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

	public function testManyToOneGeneratesLeftJoin(): void {
		$orderRepo = $this->getRepository(Order::class);

		/** @var array{0: IQueryBuilder, 1: array} $result */
		$result = self::invokePrivate($orderRepo, 'getJoinedSelectQueryBuilder', [['id' => 1], []]);
		[$qb, $relations] = $result;

		$this->assertCount(1, $relations);
		$this->assertEquals(
			'SELECT e.id AS e_id, r0.id AS r0_id, r0.name AS r0_name '
				. 'FROM *PREFIX*repository_order e '
				. 'LEFT JOIN *PREFIX*repository_merchant r0 ON e.merchant_id = r0.id '
				. 'WHERE e.id = :dcValue1',
			$this->normalizeSql($qb->getSQL()),
		);
	}

	public function testInverseSideGeneratesLeftJoin(): void {
		$customerRepo = $this->getRepository(Customer::class);

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
		$repo = $this->getRepository(PrimaryKey::class);

		/** @var array{0: IQueryBuilder, 1: array} $result */
		$result = self::invokePrivate($repo, 'getJoinedSelectQueryBuilder', [['id' => 1], []]);
		[$qb, $relations] = $result;

		$this->assertCount(0, $relations);
		$this->assertEquals(
			'SELECT e.id AS e_id, e.name AS e_name, e.not_nullable AS e_not_nullable, '
				. 'e.integer_val AS e_integer_val, e.bigint_val AS e_bigint_val, '
				. 'e.float_val AS e_float_val, e.date_val AS e_date_val '
				. 'FROM *PREFIX*repository_test_test2 e '
				. 'WHERE e.id = :dcValue1',
			$this->normalizeSql($qb->getSQL()),
		);
	}
}

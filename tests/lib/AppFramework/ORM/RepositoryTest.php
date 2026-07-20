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
use OCP\DB\Types;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\TestCase;

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
    #[JoinColumn(name: 'cart_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
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

class RepositoryTest extends TestCase {
	/** @var array<class-string, class-string<Repository>> */
	public static array $entitiesClasses = [
		NoPrimaryKey::class => NoPrimaryKeyRepository::class,
		PrimaryKey::class => PrimaryKeyRepository::class,
		Customer::class => CustomerRepository::class,
		Cart::class => CartRepository::class,
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

		$cart = new Cart();
		$cart->customer = $customer;
		$cartRepo->insert($cart);
		$this->assertNotNull($cart->id);
	}
}

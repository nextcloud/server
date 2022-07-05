<?php

namespace OCP\DB\ORM;

use DateTimeInterface;
use OCP\IDBConnection;

/**
 * @since 25.0.0
 */
interface IEntityManager {
	/**
	 * Creates a new Query object.
	 *
	 * @param string $dql The DQL string.
	 * @since 25.0.0
	 */
	public function createQuery(string $dql = ''): IQuery;

	/**
	 * Flushes all changes to objects that have been queued up to now to the database.
	 * This effectively synchronizes the in-memory state of managed objects with the
	 * database.
	 *
	 * @throws OptimisticLockException If a version check on an entity that
	 * makes use of optimistic locking fails.
	 * @throws \OCP\DB\Exception
	 * @since 25.0.0
	 */
	public function flush(): void;

	/**
	 * Finds an Entity by its identifier.
	 *
	 * @param string   $className   The class name of the entity to find.
	 * @param mixed    $id          The identity of the entity to find.
	 * @param int|null $lockMode    One of the \Doctrine\DBAL\LockMode::* constants
	 *    or NULL if no specific lock mode should be used
	 *    during the search.
	 * @param int|null $lockVersion The version of the entity to find when using
	 * optimistic locking.
	 * @psalm-param class-string<T> $className
	 * @psalm-param LockMode::*|null $lockMode
	 *
	 * @return object|null The entity instance or NULL if the entity can not be found.
	 * @psalm-return ?T
	 *
	 * @throws OptimisticLockException
	 * @throws \OCP\DB\Exception
	 *
	 * @template T
	 * @since 25.0.0
	 */
	public function find(string $className, $id, ?int $lockMode = null, ?int $lockVersion = null): ?object;

	/**
	 * Clears the EntityManager. All entities that are currently managed
	 * by this EntityManager become detached.
	 *
	 * @throws \OCP\DB\Exception If a $entityName is given, but that entity is not
	 *                           found in the mappings.
	 * @since 25.0.0
	 */
	public function clear(): void;

	/**
	 * Tells the EntityManager to make an instance managed and persistent.
	 *
	 * The entity will be entered into the database at or before transaction
	 * commit or as a result of the flush operation.
	 *
	 * NOTE: The persist operation always considers entities that are not yet known to
	 * this EntityManager as NEW. Do not pass detached entities to the persist operation.
	 *
	 * @param object $entity The instance to make managed and persistent.
	 *
	 * @throws \OCP\DB\Exception
	 * @since 25.0.0
	 */
	public function persist(object $entity): void;

	/**
	 * Removes an entity instance.
	 *
	 * A removed entity will be removed from the database at or before transaction commit
	 * or as a result of the flush operation.
	 *
	 * @param object $entity The entity instance to remove.
	 *
	  @throws \OCP\DB\Exception
	 * @since 25.0.0
	 */
	public function remove(object $entity): void;

	/**
	 * Acquire a lock on the given entity.
	 *
	 * @param int|DateTimeInterface|null $lockVersion
	 * @psalm-param LockMode::* $lockMode
	 *
	 *
	 * @throws OptimisticLockException
	 * @throws PessimisticLockException
	 * @since 25.0.0
	 */
	public function lock(object $entity, int $lockMode, $lockVersion = null): void;

	/**
	 * {@inheritdoc}
	 *
	 * @psalm-param class-string<T> $className
	 *
	 * @psalm-return IEntityRepository<T>
	 *
	 * @template T of object
	 * @since 25.0.0
	 */
	public function getRepository($className): IEntityRepository;

	/**
	 * Determines whether an entity instance is managed in this EntityManager.
	 *
	 * @return bool TRUE if this EntityManager currently manages the given entity, FALSE otherwise.
	 * @since 25.0.0
	 */
	public function contains(object $entity): bool;

	/**
	 * @return IDBConnection
	 * @since 25.0.0
	 */
	public function getConnection(): IDBConnection;
}

<?php

namespace OCP\DB\ORM;

/**
 * Contract for a Doctrine persistence layer ObjectRepository class to implement.
 *
 * @template-covariant T of object
 */
interface IEntityRepository
{
	/**
	 * Finds an object by its primary key / identifier.
	 *
	 * @param mixed $id The identifier.
	 *
	 * @return object|null The object.
	 * @psalm-return T|null
	 */
	public function find($id);

	/**
	 * Finds all objects in the repository.
	 *
	 * @return array<int, object> The objects.
	 * @psalm-return T[]
	 */
	public function findAll();

	/**
	 * Finds objects by a set of criteria.
	 *
	 * Optionally sorting and limiting details can be passed. An implementation may throw
	 * an UnexpectedValueException if certain values of the sorting or limiting details are
	 * not supported.
	 *
	 * @param array<string, mixed>       $criteria
	 * @param array<string, string>|null $orderBy
	 * @psalm-param array<string, 'asc'|'desc'|'ASC'|'DESC'>|null $orderBy
	 *
	 * @return array<int, object> The objects.
	 * @psalm-return T[]
	 *
	 * @throws \RuntimeException
	 */
	public function findBy(
		array $criteria,
		?array $orderBy = null,
		?int $limit = null,
		?int $offset = null
	);

	/**
	 * Finds a single object by a set of criteria.
	 *
	 * @param array<string, mixed> $criteria The criteria.
	 *
	 * @return object|null The object.
	 * @psalm-return T|null
	 */
	public function findOneBy(array $criteria);

	/**
	 * Returns the class name of the object managed by the repository.
	 *
	 * @psalm-return class-string<T>
	 */
	public function getClassName();
}

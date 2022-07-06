<?php

namespace OCP\DB\ORM;

interface IQuery {
    /**
	 * Enable/disable second level query (result) caching for this query.
	 */
    public function setCacheable(bool $cacheable): self;

	/**
	 * @return bool TRUE if the query results are enabled for second level cache, FALSE otherwise.
	 */
	public function isCacheable(): bool;

	/**
	 * Gets a query parameter.
	 *
	 * @param string|int $key The key (index or name) of the bound parameter.
	 *
	 * @return IParameter|null The value of the bound parameter, or NULL if not available.
	 */
	public function getParameter($key): ?IParameter;

	/**
	 * Sets a collection of query parameters.
	 *
	 * @param mixed[] $parameters
	 * @psalm-param mixed[] $parameters
	 */
	public function setParameters($parameters): self;

	/**
	 * Sets a query parameter.
	 *
	 * @param string|int      $key   The parameter position or name.
	 * @param mixed           $value The parameter value.
	 * @param string|int|null $type  The parameter type. If specified, the given value will be run through
	 *                               the type conversion of this type. This is usually not needed for
	 *                               strings and numeric types.
	 */
	public function setParameter($key, $value, $type = null): self;

	/**
	 * Sets the maximum number of results to retrieve (the "limit").
	 */
	public function setMaxResults(?int $maxResults): self;

	/**
	 * Gets the list of results for the query.
	 *
	 * @return mixed
	 */
	public function getResult();

	/**
	 * Get exactly one result or null.
	 *
	 * @return mixed
	 *
	 * @throws NonUniqueResultException
	 */
	public function getOneOrNullResult();

	/**
	 * Gets the single result of the query.
	 *
	 * Enforces the presence as well as the uniqueness of the result.
	 *
	 * If the result is not unique, a NonUniqueResultException is thrown.
	 * If there is no result, a NoResultException is thrown.
	 *
	 * @return mixed
	 *
	 * @throws NonUniqueResultException If the query result is not unique.
	 * @throws NoResultException        If the query returned no result.
	 */
	public function getSingleResult();

	public function getSingleScalarResult();

}

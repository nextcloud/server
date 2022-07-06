<?php

namespace OCP\DB\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Parameter;

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

	public function setMaxResults(): self;

	/**
	 * Gets the list of results for the query.
	 *
	 * @return mixed
	 */
	public function getResult();

}

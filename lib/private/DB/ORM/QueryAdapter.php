<?php

namespace OC\DB\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query;
use OC\DB\QueryBuilder\Parameter;
use OCP\DB\ORM\IParameter;
use OCP\DB\ORM\IQuery;
use OCP\DB\ORM\NonUniqueResultException;
use OCP\DB\ORM\NoResultException;

class QueryAdapter implements IQuery {
	private Query $query;

	public function __construct(Query $query) {
		$this->query = $query;
	}

	public function setCacheable(bool $cacheable): IQuery {
		$this->query->setCacheable($cacheable);
		return $this;
	}

	public function isCacheable(): bool {
		return $this->query->isCacheable();
	}

	public function getParameter($key): ?IParameter {
		$internal = $this->query->getParameter($key);
		return $internal === null ? new ParameterAdapter($internal) : null;
	}

	public function setParameters($parameters): IQuery {
		$this->query->setParameters($parameters);
		return $this;
	}

	public function setParameter($key, $value, $type = null): IQuery {
		$this->query->setParameter($key, $value, $type);
		return $this;
	}

	public function setMaxResults(?int $maxResults): IQuery {
		$this->query->setMaxResults($maxResults);
		return $this;
	}

	public function getResult() {
		return $this->query->getResult();
	}

	public function getArrayResult() {
		return $this->query->getArrayResult();
	}

	public function getOneOrNullResult() {
		return $this->query->getOneOrNullResult();
	}

	public function getSingleResult() {
		try {
			return $this->query->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			throw new NoResultException($e);
		} catch (\Doctrine\ORM\NonUniqueResultException $e) {
			throw new NonUniqueResultException($e);
		}
	}

	public function getSingleScalarResult() {
		try {
			return $this->query->getSingleScalarResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			throw new NoResultException($e);
		} catch (\Doctrine\ORM\NonUniqueResultException $e) {
			throw new NonUniqueResultException($e);
		}
	}

	public function getSql(): string {
		return $this->query->getSQL();
	}

	/**
	 * Get all defined parameters.
	 *
	 * @return ArrayCollection The defined query parameters.
	 * @psalm-return ArrayCollection<int, Query\Parameter>
	 */
	public function getParameters(): ArrayCollection
	{
		return $this->query->getParameters()
			->map(fn (Query\Parameter $parameter) => new Parameter($parameter));
	}
}

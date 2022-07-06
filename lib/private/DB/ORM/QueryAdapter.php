<?php

namespace OC\DB\ORM;

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
		return new ParameterAdapter($this->query->getParameter($key));
	}

	public function setParameters($parameters): IQuery {
		$this->query->setParameters($parameters);
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

	public function getOneOrNullResult() {
		return $this->query->getOneOrNullResult();
	}

	public function getSingleResult() {
		return $this->query->getSingleResult();
	}

	public function getSingleScalarResult() {
		return $this->query->getSingleScalarResult();
	}
}

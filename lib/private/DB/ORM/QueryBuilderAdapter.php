<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Carl Schwan <carl@carlschwan.eu>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\DB\ORM;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Parameter;
use OC\DB\ConnectionAdapter;
use OCP\DB\ORM\IQueryBuilder;
use OCP\IDBConnection;

class QueryBuilderAdapter implements IQueryBuilder {
	private QueryBuilder $qb;

	public function __construct(QueryBuilder $queryBuilder) {
		$this->qb = $queryBuilder;
	}

	public function expr(): Query\IExpression {
		return new ExpressionAdapter($this->qb->expr());
	}

	public function setParameter($key, $value, $type = null): self {
		$this->qb->setParameter($key, $value, $type);
		return $this;
	}

	public function setParameters(array $parameters): self {
		$ormParameters = []
		foreach ($parameters as $key => $value) {
			$ormParameters[] = new Parameter($key, $value);
		}
		$this->qb->setParameters(new ArrayCollection($ormParameters));
		return $this;
	}

	public function getParameters(): array {
		$ormParameters = []
		foreach ($this->qb->getParameters() as $value) {
			$ormParameters[] = new ParameterAdapter($value);
		}
		return $ormParameters;
	}

    /**
     * Gets a (previously set) query parameter of the query being constructed.
     *
     * @param string|int $key The key (index or name) of the bound parameter.
     *
     * @return ?IParameter The value of the bound parameter.
     */
    public function getParameter($key): ?IParameter;

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param int|null $firstResult The first result to return.
     *
     * @return $this
     */
    public function setFirstResult(?int $firstResult): self

    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this QueryBuilder.
     *
     * @return int|null The position of the first result.
     */
    public function getFirstResult(): ?int;

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param int|null $maxResults The maximum number of results to retrieve.
     */
    public function setMaxResults(?int $maxResults): self;

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns NULL if {@link setMaxResults} was not applied to this query builder.
     *
     * @return int|null Maximum number of results.
     */
    public function getMaxResults(): ?int;

    /**
     * Specifies an item that is to be returned in the query result.
     * Replaces any previously specified selections, if any.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u', 'p')
     *         ->from('User', 'u')
     *         ->leftJoin('u.Phonenumbers', 'p');
     * </code>
     *
     * @param mixed $select The selection expressions.
     */
    public function select($select = null): self;

    /**
     * Adds a DISTINCT flag to this query.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->distinct()
     *         ->from('User', 'u');
     * </code>
     */
    public function distinct(bool $flag = true): self;

    /**
     * Adds an item that is to be returned in the query result.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->addSelect('p')
     *         ->from('User', 'u')
     *         ->leftJoin('u.Phonenumbers', 'p');
     * </code>
     *
     * @param mixed $select The selection expression.
     */
    public function addSelect($select = null): self;

    /**
     * Turns the query being built into a bulk delete query that ranges over
     * a certain entity type.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->delete('User', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter('user_id', 1);
     * </code>
     *
     * @param string|null $delete The class/type whose instances are subject to the deletion.
     * @param string|null $alias  The class/type alias used in the constructed query.
     */
    public function delete(?string $delete = null, ?string $alias = null): self;

    /**
     * Turns the query being built into a bulk update query that ranges over
     * a certain entity type.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->update('User', 'u')
     *         ->set('u.password', '?1')
     *         ->where('u.id = ?2');
     * </code>
     *
     * @param string|null $update The class/type whose instances are subject to the update.
     * @param string|null $alias  The class/type alias used in the constructed query.
     */
    public function update(?string $update = null, ?string $alias = null): self;

    /**
     * Creates and adds a query root corresponding to the entity identified by the given alias,
     * forming a cartesian product with any existing query roots.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u');
     * </code>
     *
     * @param string      $from    The class name.
     * @param string      $alias   The alias of the class.
     * @param string|null $indexBy The index for the from.
     *
     * @return $this
     */
    public function from(string $from, string $alias, ?string $indexBy = null);

    /**
     * Updates a query root corresponding to an entity setting its index by. This method is intended to be used with
     * EntityRepository->createQueryBuilder(), which creates the initial FROM clause and do not allow you to update it
     * setting an index by.
     *
     * <code>
     *     $qb = $userRepository->createQueryBuilder('u')
     *         ->indexBy('u', 'u.id');
     *
     *     // Is equivalent to...
     *
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u', 'u.id');
     * </code>
     *
     * @param string $alias   The root alias of the class.
     * @param string $indexBy The index for the from.
     *
     * @throws Query\QueryException
     */
    public function indexBy(string $alias, string $indexBy): self;

    /**
     * Creates and adds a join over an entity association to the query.
     *
     * The entities in the joined association will be fetched as part of the query
     * result if the alias used for the joined association is placed in the select
     * expressions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->join('u.Phonenumbers', 'p', Expr\Join::WITH, 'p.is_primary = 1');
     * </code>
     *
     * @param string                                     $join          The relationship to join.
     * @param string                                     $alias         The alias of the join.
     * @param string|null                                $conditionType The condition type constant. Either ON or WITH.
     * @param string|Expr\Comparison|Expr\Composite|null $condition     The condition for the join.
     * @param string|null                                $indexBy       The index for the join.
     * @psalm-param Expr\Join::ON|Expr\Join::WITH|null $conditionType
     */
    public function join(string $join, string $alias, ?string $conditionType = null, $condition = null, ?string $indexBy = null): self;

    /**
     * Creates and adds a join over an entity association to the query.
     *
     * The entities in the joined association will be fetched as part of the query
     * result if the alias used for the joined association is placed in the select
     * expressions.
     *
     *     [php]
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->innerJoin('u.Phonenumbers', 'p', Expr\Join::WITH, 'p.is_primary = 1');
     *
     * @param string                                     $join          The relationship to join.
     * @param string                                     $alias         The alias of the join.
     * @param string|null                                $conditionType The condition type constant. Either ON or WITH.
     * @param string|Expr\Comparison|Expr\Composite|null $condition     The condition for the join.
     * @param string|null                                $indexBy       The index for the join.
     * @psalm-param Expr\Join::ON|Expr\Join::WITH|null $conditionType
     */
    public function innerJoin(string $join, string $alias, ?string $conditionType = null, $condition = null, ?string $indexBy = null): self;

    /**
     * Creates and adds a left join over an entity association to the query.
     *
     * The entities in the joined association will be fetched as part of the query
     * result if the alias used for the joined association is placed in the select
     * expressions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->leftJoin('u.Phonenumbers', 'p', Expr\Join::WITH, 'p.is_primary = 1');
     * </code>
     *
     * @param string                                     $join          The relationship to join.
     * @param string                                     $alias         The alias of the join.
     * @param string|null                                $conditionType The condition type constant. Either ON or WITH.
     * @param string|Expr\Comparison|Expr\Composite|null $condition     The condition for the join.
     * @param string|null                                $indexBy       The index for the join.
     * @psalm-param Expr\Join::ON|Expr\Join::WITH|null $conditionType
     *
     * @return $this
     */
    public function leftJoin(string $join, string $alias, $conditionType = null, $condition = null, ?string $indexBy = null);

    /**
     * Sets a new value for a field in a bulk update query.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->update('User', 'u')
     *         ->set('u.password', '?1')
     *         ->where('u.id = ?2');
     * </code>
     *
     * @param string $key   The key/field to set.
     * @param mixed  $value The value, expression, placeholder, etc.
     */
    public function set(string $key, $value): self;

    /**
     * Specifies one or more restrictions to the query result.
     * Replaces any previously specified restrictions, if any.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.id = ?');
     *
     *     // You can optionally programmatically build and/or expressions
     *     $qb = $em->createQueryBuilder();
     *
     *     $or = $qb->expr()->orX();
     *     $or->add($qb->expr()->eq('u.id', 1));
     *     $or->add($qb->expr()->eq('u.id', 2));
     *
     *     $qb->update('User', 'u')
     *         ->set('u.password', '?')
     *         ->where($or);
     * </code>
     *
     * @param mixed $predicates The restriction predicates.
     *
     * @return $this
     */
    public function where($predicates)
    {
        if (! (func_num_args() === 1 && $predicates instanceof Expr\Composite)) {
            $predicates = new Expr\Andx(func_get_args());
        }

        return $this->add('where', $predicates);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * conjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.username LIKE ?')
     *         ->andWhere('u.is_active = 1');
     * </code>
     *
     * @see where()
     *
     * @param mixed $where The query restrictions.
     *
     * @return $this
     */
    public function andWhere()
    {
        $args  = func_get_args();
        $where = $this->getDQLPart('where');

        if ($where instanceof Expr\Andx) {
            $where->addMultiple($args);
        } else {
            array_unshift($args, $where);
            $where = new Expr\Andx($args);
        }

        return $this->add('where', $where);
    }

    /**
     * Adds one or more restrictions to the query results, forming a logical
     * disjunction with any previously specified restrictions.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('u.id = 1')
     *         ->orWhere('u.id = 2');
     * </code>
     *
     * @see where()
     *
     * @param mixed $where The WHERE statement.
     *
     * @return $this
     */
    public function orWhere()
    {
        $args  = func_get_args();
        $where = $this->getDQLPart('where');

        if ($where instanceof Expr\Orx) {
            $where->addMultiple($args);
        } else {
            array_unshift($args, $where);
            $where = new Expr\Orx($args);
        }

        return $this->add('where', $where);
    }

    /**
     * Specifies a grouping over the results of the query.
     * Replaces any previously specified groupings, if any.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->groupBy('u.id');
     * </code>
     *
     * @param string $groupBy The grouping expression.
     *
     * @return $this
     */
    public function groupBy($groupBy)
    {
        return $this->add('groupBy', new Expr\GroupBy(func_get_args()));
    }

    /**
     * Adds a grouping expression to the query.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->groupBy('u.lastLogin')
     *         ->addGroupBy('u.createdAt');
     * </code>
     *
     * @param string $groupBy The grouping expression.
     *
     * @return $this
     */
    public function addGroupBy($groupBy)
    {
        return $this->add('groupBy', new Expr\GroupBy(func_get_args()), true);
    }

    /**
     * Specifies a restriction over the groups of the query.
     * Replaces any previous having restrictions, if any.
     *
     * @param mixed $having The restriction over the groups.
     *
     * @return $this
     */
    public function having($having)
    {
        if (! (func_num_args() === 1 && ($having instanceof Expr\Andx || $having instanceof Expr\Orx))) {
            $having = new Expr\Andx(func_get_args());
        }

        return $this->add('having', $having);
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * conjunction with any existing having restrictions.
     *
     * @param mixed $having The restriction to append.
     *
     * @return $this
     */
    public function andHaving($having)
    {
        $args   = func_get_args();
        $having = $this->getDQLPart('having');

        if ($having instanceof Expr\Andx) {
            $having->addMultiple($args);
        } else {
            array_unshift($args, $having);
            $having = new Expr\Andx($args);
        }

        return $this->add('having', $having);
    }

    /**
     * Adds a restriction over the groups of the query, forming a logical
     * disjunction with any existing having restrictions.
     *
     * @param mixed $having The restriction to add.
     *
     * @return $this
     */
    public function orHaving($having)
    {
        $args   = func_get_args();
        $having = $this->getDQLPart('having');

        if ($having instanceof Expr\Orx) {
            $having->addMultiple($args);
        } else {
            array_unshift($args, $having);
            $having = new Expr\Orx($args);
        }

        return $this->add('having', $having);
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string|Expr\OrderBy $sort  The ordering expression.
     * @param string|null         $order The ordering direction.
     *
     * @return $this
     */
    public function orderBy($sort, $order = null)
    {
        $orderBy = $sort instanceof Expr\OrderBy ? $sort : new Expr\OrderBy($sort, $order);

        return $this->add('orderBy', $orderBy);
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param string|Expr\OrderBy $sort  The ordering expression.
     * @param string|null         $order The ordering direction.
     *
     * @return $this
     */
    public function addOrderBy($sort, $order = null)
    {
        $orderBy = $sort instanceof Expr\OrderBy ? $sort : new Expr\OrderBy($sort, $order);

        return $this->add('orderBy', $orderBy, true);
    }
}

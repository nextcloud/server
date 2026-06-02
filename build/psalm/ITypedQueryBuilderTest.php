<?php

declare(strict_types=1);

use OCP\IDBConnection;
use OCP\Server;

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$qb = Server::get(IDBConnection::class)->getTypedQueryBuilder();

$qb->selectColumns('a', 'b');
$qb->selectColumns('c');

$qb->selectColumnsDistinct('d', 'e');
$qb->selectColumnsDistinct('f');

$qb->selectAlias('g', 'h');
$qb->selectAlias($qb->func()->lower('i'), 'j');

$qb
	->setParameter('k', 'l')
	->setParameters([])
	->setFirstResult(0)
	->setMaxResults(0)
	->delete()
	->update()
	->insert()
	->from('m')
	->join('n', 'o', 'p')
	->innerJoin('q', 'r', 's')
	->leftJoin('t', 'u', 'v')
	->rightJoin('w', 'x', 'y')
	->set('z', '1')
	->where()
	->andWhere()
	->orWhere()
	->groupBy()
	->addGroupBy()
	->setValue('2', '3')
	->values([])
	->having()
	->andHaving()
	->orHaving()
	->orderBy('4')
	->addOrderBy('5')
	->resetQueryParts()
	->resetQueryPart('6')
	->hintShardKey('7', '8')
	->runAcrossAllShards()
	->forUpdate();

/** @psalm-check-type-exact $result = \OCP\DB\IResult<'a'|'b'|'c'|'d'|'e'|'f'|'h'|'j'> */
$result = $qb->executeQuery();

/** @psalm-check-type-exact $rows = array<'a'|'b'|'c'|'d'|'e'|'f'|'h'|'j', mixed>|false */
$rows = $result->fetch(\PDO::FETCH_ASSOC);

/** @psalm-check-type-exact $rows = array<'a'|'b'|'c'|'d'|'e'|'f'|'h'|'j', mixed>|false */
$rows = $result->fetchAssociative();

/** @psalm-check-type-exact $rows = list<array<'a'|'b'|'c'|'d'|'e'|'f'|'h'|'j', mixed>> */
$rows = $result->fetchAll(\PDO::FETCH_ASSOC);

/** @psalm-check-type-exact $rows = list<array<'a'|'b'|'c'|'d'|'e'|'f'|'h'|'j', mixed>> */
$rows = $result->fetchAllAssociative();

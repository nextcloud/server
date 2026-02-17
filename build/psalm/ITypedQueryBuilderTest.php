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

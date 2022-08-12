<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Carl Schwan <carl@carlschwan.eu>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\DB\ORM;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query\Expr;
use OC\DB\ConnectionAdapter;
use OCP\DB\ORM\Query\IExpression;
use OCP\IDBConnection;

class ExpressionAdapter implements IExpression {
	private Expr $expr;

	public function __construct(Expr $expr) {
		$this->expr = $expr;
	}
}

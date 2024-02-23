<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\DB\QueryBuilder\ExpressionBuilder;

use OC\DB\ConnectionAdapter;
use OC\DB\QueryBuilder\QueryFunction;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;

class MySqlExpressionBuilder extends ExpressionBuilder {
	/** @var string */
	protected $collation;

	/**
	 * @param ConnectionAdapter $connection
	 * @param IQueryBuilder $queryBuilder
	 */
	public function __construct(ConnectionAdapter $connection, IQueryBuilder $queryBuilder) {
		parent::__construct($connection, $queryBuilder);

		$params = $connection->getInner()->getParams();
		$this->collation = $params['collation'] ?? (($params['charset'] ?? 'utf8') . '_general_ci');
	}

	/**
	 * @inheritdoc
	 */
	public function iLike($x, $y, $type = null): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnName($y);
		return $this->expressionBuilder->comparison($x, ' COLLATE ' . $this->collation . ' LIKE', $y);
	}

	/**
	 * Returns a IQueryFunction that casts the column to the given type
	 *
	 * @param string|IQueryFunction $column
	 * @param mixed $type One of IQueryBuilder::PARAM_*
	 * @psalm-param IQueryBuilder::PARAM_* $type
	 * @return IQueryFunction
	 */
	public function castColumn($column, $type): IQueryFunction {
		switch ($type) {
			case IQueryBuilder::PARAM_STR:
				return new QueryFunction('CAST(' . $this->helper->quoteColumnName($column) . ' AS CHAR)');
			default:
				return parent::castColumn($column, $type);
		}
	}
}

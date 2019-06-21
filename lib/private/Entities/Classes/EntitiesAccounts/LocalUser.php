<?php
declare(strict_types=1);


/**
 * Entities - Entity & Groups of Entities
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019, Maxence Lange <maxence@artificial-owl.com>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\Entities\Classes\IEntitiesAccounts;


use OC;
use OC\Entities\Db\CoreRequestBuilder;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\Entities\IEntitiesQueryBuilder;
use OCP\Entities\Implementation\IEntitiesAccounts\IEntitiesAccounts;
use OCP\Entities\Implementation\IEntitiesAccounts\IEntitiesAccountsSearchDuplicate;
use OCP\Entities\Implementation\IEntitiesAccounts\IEntitiesAccountsSearchEntities;
use OCP\Entities\Model\IEntityAccount;


/**
 * Class LocalUser
 *
 * @package OC\Entities\Classes\IEntitiesAccounts
 */
class LocalUser implements
	IEntitiesAccounts,
	IEntitiesAccountsSearchEntities,
	IEntitiesAccountsSearchDuplicate {


	const TYPE = 'local_user';


	/**
	 * @param IEntitiesQueryBuilder $qb
	 * @param string $needle
	 *
	 * @return ICompositeExpression
	 */
	public function exprSearchEntities(IEntitiesQueryBuilder $qb, string $needle
	): ICompositeExpression {
		$qb->from(CoreRequestBuilder::TABLE_ENTITIES_ACCOUNTS, 'ea');

		$expr = $qb->expr();
		$dbConn = $qb->getConnection();

		$andX = $expr->andX();
		$andX->add($expr->eq('ea.id', 'e.owner_id'));
		$andX->add($expr->eq('ea.type', $qb->createNamedParameter(self::TYPE)));
		$andX->add(
			$expr->iLike(
				'ea.account',
				$qb->createNamedParameter('%' . $dbConn->escapeLikeParameter($needle) . '%')
			)
		);

		return $andX;
	}


	/**
	 * @param IEntitiesQueryBuilder $qb
	 * @param IEntityAccount $account
	 */
	public function buildSearchDuplicate(IEntitiesQueryBuilder $qb, IEntityAccount $account) {
		$qb->limitToType($account->getType());
		$qb->limitToAccount($account->getAccount());
	}

}


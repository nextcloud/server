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
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Entities\IEntitiesQueryBuilder;
use OCP\Entities\Implementation\IEntitiesAccounts\IEntitiesAccounts;
use OCP\Entities\Implementation\IEntitiesAccounts\IEntitiesAccountsSearchDuplicate;
use OCP\Entities\Model\IEntity;
use OCP\Entities\Model\IEntityAccount;


/**
 * Class MailAddress
 *
 * @package OC\Entities\Classes\IEntitiesAccounts
 */
class MailAddress implements
	IEntitiesAccounts,
	IEntitiesAccountsSearchDuplicate {


	const TYPE = 'mail_address';


	/**
	 * @param IEntitiesQueryBuilder $qb
	 * @param IEntityAccount $account
	 */
	public function buildSearchDuplicate(IEntitiesQueryBuilder $qb, IEntityAccount $account) {

		$qb->limitToType($account->getType());
		$qb->limitToAccount($account->getAccount());

	}

}


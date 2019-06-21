<?php
declare(strict_types=1);


/**
 * Nextcloud - Social Support
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018, Maxence Lange <maxence@artificial-owl.com>
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


namespace OC\Entities\Db;


use DateTime;
use Exception;
use OC\Entities\Exceptions\EntityAccountNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Entities\Implementation\IEntitiesAccounts\IEntitiesAccountsSearchAccounts;
use OCP\Entities\Model\IEntityAccount;
use stdClass;

/**
 * Class EntitiesRequest
 *
 * @package OC\Entities\Db
 */
class EntitiesAccountsRequest extends EntitiesAccountsRequestBuilder {


	/**
	 * @param IEntityAccount $account
	 *
	 * @throws Exception
	 */
	public function create(IEntityAccount $account) {
		$now = new DateTime('now');

		$qb =
			$this->getEntitiesAccountsInsertSql(
				'create a new EntityAccount: ' . json_encode($account)
			);
		$qb->setValue('id', $qb->createNamedParameter($account->getId()))
		   ->setValue('type', $qb->createNamedParameter($account->getType()))
		   ->setValue('account', $qb->createNamedParameter($account->getAccount()))
		   ->setValue('delete_on', $qb->createNamedParameter($account->getDeleteOn()))
		   ->setValue('creation', $qb->createNamedParameter($now, IQueryBuilder::PARAM_DATE));

		$qb->execute();

		$account->setCreation($now->getTimestamp());
	}


	/**
	 * @param string $accountId
	 *
	 * @return IEntityAccount
	 * @throws EntityAccountNotFoundException
	 */
	public function getFromId(string $accountId): IEntityAccount {
		$qb = $this->getEntitiesAccountsSelectSql(
			'get EntityAccount from Id - Id: ' . $accountId
		);
		$qb->limitToIdString($accountId);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $account
	 * @param string $type
	 *
	 * @return IEntityAccount
	 * @throws EntityAccountNotFoundException
	 */
	public function getFromAccount(string $account, string $type = ''): IEntityAccount {
		$qb = $this->getEntitiesAccountsSelectSql(
			'get EntityAccount from Account - account: ' . $account . ' - type: ' . $type
		);
		$qb->limitToAccount($account);

		if ($type !== '') {
			$qb->limitToType($type);
		}

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $type
	 *
	 * @return IEntityAccount[]
	 */
	public function getAll(string $type = ''): array {
		$qb = $this->getEntitiesAccountsSelectSql('get all EntityAccounts - type: ' . $type);
		if ($type !== '') {
			$qb->limitToType($type);
		}

		$qb->orderBy('type', 'asc');

		return $this->getListFromRequest($qb);
	}

//
//	/**
//	 * @param string $userId
//	 *
//	 * @return IEntityAccount
//	 * @throws EntityAccountNotFoundException
//	 */
//	public function getFromLocalUserId(string $userId) {
//		$qb =
//			$this->getEntitiesAccountsSelectSql('get EntityAccount from LocalUserId - ' . $userId);
//
//		$qb->limitToType(LocalUser::TYPE);
//		$qb->limitToAccount($userId);
//
//		return $this->getItemFromRequest($qb);
//	}


	/**
	 * @param string $needle
	 * @param string $type
	 * @param stdClass[] $classes
	 *
	 * @return IEntityAccount[]
	 */
	public function search(string $needle, string $type = '', array $classes = []): array {
		$qb = $this->getEntitiesAccountsSelectSql(
			'search EntityAccounts - needle: ' . $needle . ' - type: ' . $type . ' - classes: '
			. json_encode($classes)
		);
		if ($type !== '') {
			$qb->limitToType($type);
		}

		$qb->orderBy('type', 'asc');

		$needle = $this->dbConnection->escapeLikeParameter($needle);
		$qb->searchInAccount('%' . $needle . '%');

		if (sizeof($classes) > 0) {
			$orX = $qb->expr()
					  ->orX();
			foreach ($classes as $class) {
				/** @var IEntitiesAccountsSearchAccounts $class */
				$orX->add($class->exprSearchAccounts($qb, $needle));
			}

			$qb->orWhere($orX);
		}

		return $this->getListFromRequest($qb);
	}


	/**
	 * @param string $accountId
	 *
	 * @throws Exception
	 */
	public function delete(string $accountId) {
		$qb = $this->getEntitiesAccountsDeleteSql('delete EntityAccount - id: ' . $accountId);
		$qb->limitToIdString($accountId);

		$qb->execute();
	}


	/**
	 * @param IQueryBuilder $qb
	 *
	 * @return IEntityAccount
	 * @throws EntityAccountNotFoundException
	 */
	public function getItemFromRequest(IQueryBuilder $qb): IEntityAccount {
		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new EntityAccountNotFoundException('EntityAccount not found');
		}

		return $this->parseEntitiesAccountsSelectSql($data);
	}


	/**
	 * @param IQueryBuilder $qb
	 *
	 * @return IEntityAccount[]
	 */
	public function getListFromRequest(IQueryBuilder $qb): array {
		$accounts = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$accounts[] = $this->parseEntitiesAccountsSelectSql($data);
		}
		$cursor->closeCursor();

		return $accounts;
	}


	/**
	 *
	 * @throws Exception
	 */
	public function clearAll(): void {
		$qb = $this->getEntitiesAccountsDeleteSql('clear all EntityAccounts');

		$qb->execute();
	}

}


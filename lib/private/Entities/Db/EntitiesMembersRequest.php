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
use OC\Entities\Exceptions\EntityMemberNotFoundException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Entities\Model\IEntity;
use OCP\Entities\Model\IEntityAccount;
use OCP\Entities\Model\IEntityMember;


/**
 * Class EntitiesMembersRequest
 *
 * @package OC\Entities\Db
 */
class EntitiesMembersRequest extends EntitiesMembersRequestBuilder {


	public function create(IEntityMember $member) {
		$now = new DateTime('now');

		$qb = $this->getEntitiesMembersInsertSql(
			'create a new EntityMember: ' . json_encode($member)
		);
		$qb->setValue('id', $qb->createNamedParameter($member->getId()))
		   ->setValue('entity_id', $qb->createNamedParameter($member->getEntityId()))
		   ->setValue('account_id', $qb->createNamedParameter($member->getAccountId()))
		   ->setValue('status', $qb->createNamedParameter($member->getStatus()))
		   ->setValue('level', $qb->createNamedParameter($member->getLevel()))
		   ->setValue('creation', $qb->createNamedParameter($now, IQueryBuilder::PARAM_DATE));

		$qb->execute();

		$member->setCreation($now->getTimestamp());
	}


	/**
	 * @param string $accountId
	 * @param string $entityId
	 *
	 * @return IEntityMember
	 * @throws EntityMemberNotFoundException
	 */
	public function getMember(string $accountId, string $entityId): IEntityMember {
		$qb = $this->getEntitiesMembersSelectSql(
			'get EntityMember by Account+Entity Ids - accountId: ' . $accountId . ' - entityId: '
			. $entityId
		);
		$qb->limitToEntityId($entityId);
		$qb->limitToAccountId($accountId);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param string $memberId
	 *
	 * @return IEntityMember
	 * @throws EntityMemberNotFoundException
	 */
	public function getFromId(string $memberId) {
		$qb =
			$this->getEntitiesMembersSelectSql('get EntityMember from Id - memberId: ' . $memberId);
		$qb->leftJoinEntity();
		$qb->leftJoinEntityAccount();
		$qb->limitToIdString($memberId);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @param IEntity $entity
	 *
	 * @return IEntityMember[]
	 */
	public function getMembers(IEntity $entity): array {
		$qb = $this->getEntitiesMembersSelectSql(
			' get all EntityMembers from an Entity: ' . json_encode($entity)
		);
		$qb->leftJoinEntityAccount();

		$qb->limitToEntityId($entity->getId());

		return $this->getListFromRequest($qb);
	}


	/**
	 * @param IEntityAccount $account
	 *
	 * @return IEntityMember[]
	 */
	public function getMembership(IEntityAccount $account): array {
		$qb = $this->getEntitiesMembersSelectSql(
			'get EntityMembers from an account: ' . json_encode($account)
		);
		$qb->leftJoinEntity();
		$qb->leftJoinEntityAccount();

		$qb->limitToAccountId($account->getId());

		return $this->getListFromRequest($qb);
	}




	/**
	 * @param string $memberId
	 *
	 * @throws Exception
	 */
	public function delete(string $memberId) {
		$qb = $this->getEntitiesMembersDeleteSql('delete EntityMember - id: ' . $memberId);
		$qb->limitToIdString($memberId);

		$qb->execute();
	}





	/**
	 * @param IQueryBuilder $qb
	 *
	 * @return IEntityMember
	 * @throws EntityMemberNotFoundException
	 */
	public function getItemFromRequest(IQueryBuilder $qb): IEntityMember {
		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new EntityMemberNotFoundException();
		}

		return $this->parseEntitiesMembersSelectSql($data);
	}


	/**
	 * @param IQueryBuilder $qb
	 *
	 * @return IEntityMember[]
	 */
	public function getListFromRequest(IQueryBuilder $qb): array {
		$members = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$members[] = $this->parseEntitiesMembersSelectSql($data);
		}
		$cursor->closeCursor();

		return $members;
	}


	/**
	 *
	 * @throws Exception
	 */
	public function clearAll(): void {
		$qb = $this->getEntitiesMembersDeleteSql('clear all EntityMembers');

		$qb->execute();
	}


}


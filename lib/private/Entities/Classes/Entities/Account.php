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


namespace OC\Entities\Classes\IEntities;


use OC;
use OCP\Entities\IEntitiesQueryBuilder;
use OCP\Entities\Implementation\IEntities\IEntities;
use OCP\Entities\Implementation\IEntities\IEntitiesOwnerIsNotMember;
use OCP\Entities\Implementation\IEntities\IEntitiesSearchDuplicate;
use OCP\Entities\Model\IEntity;


class Account implements
	IEntities,
	IEntitiesOwnerIsNotMember,
	IEntitiesSearchDuplicate {


	const TYPE = 'account';


	/**
	 * @param IEntitiesQueryBuilder $qb
	 * @param IEntity $entity
	 */
	public function buildSearchDuplicate(IEntitiesQueryBuilder $qb, IEntity $entity) {
		$qb->limitToType($entity->getType());
		$qb->limitToOwnerId($entity->getOwnerId());
		$qb->limitToName($entity->getName());
	}

}


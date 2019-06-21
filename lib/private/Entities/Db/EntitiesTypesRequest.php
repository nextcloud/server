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


use OCP\Entities\Model\IEntityType;

/**
 * Class EntitiesRequest
 *
 * @package OC\Entities\Db
 */
class EntitiesTypesRequest extends EntitiesTypesRequestBuilder {


	/**
	 * @param IEntityType $entityType
	 */
	public function create(IEntityType $entityType) {
		$qb = $this->getEntitiesTypesInsertSql('create a new EntityType: ' . json_encode($entityType));
		$qb->setValue('type', $qb->createNamedParameter($entityType->getType()))
		   ->setValue('interface', $qb->createNamedParameter($entityType->getInterface()))
		   ->setValue('class', $qb->createNamedParameter($entityType->getClassName()));

		$qb->execute();
	}


	/**
	 * @param string $interface
	 *
	 * @return IEntityType[]
	 */
	public function getClasses(string $interface = ''): array {
		$qb = $this->getEntitiesTypesSelectSql('get all EntityTypes - interface: ' . $interface);
		if ($interface !== '') {
			$qb->limitToInterface($interface);
		}

		$entities = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$entities[] = $this->parseEntitiesTypesSelectSql($data);
		}
		$cursor->closeCursor();

		return $entities;
	}


	public function clearAll(): void {
		$qb = $this->getEntitiesTypesDeleteSql('clear all EntityTypes');

		$qb->execute();
	}

}


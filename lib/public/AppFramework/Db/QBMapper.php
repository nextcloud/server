<?php

declare(strict_types=1);

/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Marius David Wieschollek <git.public@mdns.eu>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\AppFramework\Db;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Simple parent class for inheriting your data access layer from. This class
 * may be subject to change in the future
 *
 * @since 14.0.0
 */
abstract class QBMapper extends AQBBaseMapper {

	/**
	 * Deletes an entity from the table
	 * @param BaseEntity $entity the entity that should be deleted
	 * @return BaseEntity the deleted entity
	 * @since 14.0.0
	 */
	public function delete(BaseEntity $entity): BaseEntity {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->tableName)
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($entity->getId()))
			);
		$qb->execute();
		return $entity;
	}


	/**
	 * Creates a new entry in the db from an entity
	 * @param BaseEntity $entity the entity that should be created
	 * @return BaseEntity the saved entity with the set id
	 * @since 14.0.0
	 * @suppress SqlInjectionChecker
	 */
	public function insert(BaseEntity $entity): BaseEntity {
		// get updated fields to save, fields have to be set using a setter to
		// be saved
		$properties = $entity->getUpdatedFields();

		$qb = $this->db->getQueryBuilder();
		$qb->insert($this->tableName);

		// build the fields
		foreach($properties as $property => $updated) {
			$column = $entity->propertyToColumn($property);
			$getter = 'get' . ucfirst($property);
			$value = $entity->$getter();

			$type = $this->getParameterTypeForProperty($entity, $property);
			$qb->setValue($column, $qb->createNamedParameter($value, $type));
		}

		$qb->execute();

		if($entity->id === null) {
			$entity->setId((int)$qb->getLastInsertId());
		}

		return $entity;
	}

	/**
	 * Updates an entry in the db from an entity
	 * @throws \InvalidArgumentException if entity has no id
	 * @param BaseEntity $entity the entity that should be created
	 * @return BaseEntity the saved entity with the set id
	 * @since 14.0.0
	 * @suppress SqlInjectionChecker
	 */
	public function update(BaseEntity $entity): BaseEntity {
		// if entity wasn't changed it makes no sense to run a db query
		$properties = $entity->getUpdatedFields();
		if(\count($properties) === 0) {
			return $entity;
		}

		// entity needs an id
		$id = $entity->getId();
		if($id === null){
			throw new \InvalidArgumentException(
				'Entity which should be updated has no id');
		}

		// get updated fields to save, fields have to be set using a setter to
		// be saved
		// do not update the id field
		unset($properties['id']);

		$qb = $this->db->getQueryBuilder();
		$qb->update($this->tableName);

		// build the fields
		foreach($properties as $property => $updated) {
			$column = $entity->propertyToColumn($property);
			$getter = 'get' . ucfirst($property);
			$value = $entity->$getter();

			$type = $this->getParameterTypeForProperty($entity, $property);
			$qb->set($column, $qb->createNamedParameter($value, $type));
		}

		$qb->where(
			$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
		);
		$qb->execute();

		return $entity;
	}
}

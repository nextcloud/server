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


use daita\NcSmallPhpTools\Traits\TArrayTools;
use OC\Entities\Model\Entity;


/**
 * Class EntitiesRequestBuilder
 *
 * @package OC\Entities\Db
 */
class EntitiesRequestBuilder extends CoreRequestBuilder {


	use TArrayTools;


	/**
	 * Base of the Sql Insert request
	 *
	 * @param string $comment
	 *
	 * @return EntitiesQueryBuilder
	 */
	protected function getEntitiesInsertSql(string $comment = ''): EntitiesQueryBuilder {
		$qb = $this->getQueryBuilder($comment);
		$qb->insert(self::TABLE_ENTITIES);

		return $qb;
	}


	/**
	 * Base of the Sql Update request
	 *
	 * @param string $comment
	 *
	 * @return EntitiesQueryBuilder
	 */
	protected function getEntitiesUpdateSql(string $comment = ''): EntitiesQueryBuilder {
		$qb = $this->getQueryBuilder($comment);
		$qb->update(self::TABLE_ENTITIES);

		return $qb;
	}


	/**
	 * Base of the Sql Select request for Entities
	 *
	 * @param string $comment
	 *
	 * @return EntitiesQueryBuilder
	 */
	public function getEntitiesSelectSql(string $comment = ''): EntitiesQueryBuilder {
		$qb = $this->getQueryBuilder($comment);

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'e.id', 'e.type', 'e.owner_id', 'e.visibility', 'e.access', 'e.name', 'e.creation'
		)
		   ->from(self::TABLE_ENTITIES, 'e')
		   ->groupBy('e.id');

		$qb->setDefaultSelectAlias('e');

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @param string $comment
	 *
	 * @return EntitiesQueryBuilder
	 */
	protected function getEntitiesDeleteSql(string $comment = ''): EntitiesQueryBuilder {
		$qb = $this->getQueryBuilder($comment);
		$qb->delete(self::TABLE_ENTITIES);

		return $qb;
	}


	/**
	 * @param array $data
	 *
	 * @return Entity
	 */
	protected function parseEntitiesSelectSql(array $data): Entity {
		$entity = new Entity();
		$entity->importFromDatabase($data);

		$owner = $this->parseLeftJoinAccount($data);
		$entity->setOwner($owner);

		$viewer = $this->parseLeftJoinMember($data);
		$entity->setViewer($viewer);

		return $entity;
	}

}


<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Collaboration\Resources;


use OCP\Collaboration\Resources\ICollection;
use OCP\Collaboration\Resources\IManager;
use OCP\Collaboration\Resources\IResource;
use OCP\Collaboration\Resources\ResourceException;
use OCP\IDBConnection;
use OCP\IUser;

class Resource implements IResource {

	/** @var IManager */
	protected $manager;

	/** @var IDBConnection */
	protected $connection;

	/** @var string */
	protected $type;

	/** @var string */
	protected $id;

	/** @var string|null */
	protected $name;

	/** @var string|null */
	protected $iconClass;

	/** @var string|null */
	protected $link;

	public function __construct(
		IManager $manager,
		IDBConnection $connection,
		string $type,
		string $id
	) {
		$this->manager = $manager;
		$this->connection = $connection;
		$this->type = $type;
		$this->id = $id;
	}

	/**
	 * @return string
	 * @since 16.0.0
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @return string
	 * @since 16.0.0
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return string
	 * @since 16.0.0
	 */
	public function getName(): string {
		if ($this->name === null) {
			$this->name = $this->manager->getName($this);
		}

		return $this->name;
	}

	/**
	 * @return string
	 * @since 16.0.0
	 */
	public function getIconClass(): string {
		if ($this->iconClass === null) {
			$this->iconClass = $this->manager->getIconClass($this);
		}

		return $this->iconClass;
	}

	public function getLink(): string {
		if ($this->link === null) {
			$this->link = $this->manager->getLink($this);
		}

		return $this->link;
	}


	/**
	 * Can a user/guest access the resource
	 *
	 * @param IUser $user
	 * @return bool
	 * @since 16.0.0
	 */
	public function canAccess(IUser $user = null): bool {
		return $this->manager->canAccess($this, $user);
	}

	/**
	 * @return ICollection[]
	 * @since 16.0.0
	 */
	public function getCollections(): array {
		$collections = [];

		$query = $this->connection->getQueryBuilder();

		$query->select('collection_id')
			->from('collres_resources')
			->where($query->expr()->eq('resource_type', $query->createNamedParameter($this->getType())))
			->andWhere($query->expr()->eq('resource_id', $query->createNamedParameter($this->getId())));

		$result = $query->execute();
		while ($row = $result->fetch()) {
			$collections[] = $this->manager->getCollection((int) $row['collection_id']);
		}
		$result->closeCursor();

		return $collections;
	}
}

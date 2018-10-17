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
use OCP\Collaboration\Resources\IProvider;
use OCP\Collaboration\Resources\IResource;
use OCP\Collaboration\Resources\ResourceException;
use OCP\IDBConnection;
use OCP\IUser;

class Manager implements IManager {

	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param int $id
	 * @return ICollection
	 * @since 15.0.0
	 */
	public function getCollection(int $id): ICollection {
		return new Collection($this, $this->connection, $id);
	}

	/**
	 * @param string $type
	 * @param string $id
	 * @return IResource
	 * @since 15.0.0
	 */
	public function getResource(string $type, string $id): IResource {
		return new Resource($this, $this->connection, $type, $id);
	}

	/**
	 * @return IProvider[]
	 * @since 15.0.0
	 */
	public function getProviders(): array {
		return [];
	}

	/**
	 * Get the display name of a resource
	 *
	 * @param IResource $resource
	 * @return string
	 * @since 15.0.0
	 */
	public function getName(IResource $resource): string {
		foreach ($this->getProviders() as $provider) {
			try {
				return $provider->getName($resource);
			} catch (ResourceException $e) {
			}
		}

		return '';
	}

	/**
	 * Can a user/guest access the collection
	 *
	 * @param IResource $resource
	 * @param IUser $user
	 * @return bool
	 * @since 15.0.0
	 */
	public function canAccess(IResource $resource, IUser $user = null): bool {
		foreach ($this->getProviders() as $provider) {
			try {
				if ($provider->canAccess($resource, $user)) {
					return true;
				}
			} catch (ResourceException $e) {
			}
		}

		return false;
	}
}

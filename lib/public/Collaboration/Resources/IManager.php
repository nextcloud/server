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

namespace OCP\Collaboration\Resources;

/**
 * @since 16.0.0
 */
interface IManager extends IProvider {

	/**
	 * @param int $id
	 * @return ICollection
	 * @throws CollectionException when the collection could not be found
	 * @since 16.0.0
	 */
	public function getCollection(int $id): ICollection;

	/**
	 * @param string $name
	 * @return ICollection
	 * @since 16.0.0
	 */
	public function newCollection(string $name): ICollection;


	/**
	 * @param string $name
	 * @return ICollection
	 * @since 16.0.0
	 */
	public function renameCollection(int $id, string $name): ICollection;

	/**
	 * @param string $type
	 * @param string $id
	 * @return IResource
	 * @since 16.0.0
	 */
	public function getResource(string $type, string $id): IResource;

	/**
	 * @param IProvider $provider
	 */
	public function registerResourceProvider(IProvider $provider): void;
}

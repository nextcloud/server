<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_Versions\Versions;

use OCP\Files\Storage\IStorage;

/**
 * @since 15.0.0
 */
interface IVersionManager extends IVersionBackend {
	/**
	 * Register a new backend
	 *
	 * @param string $storageType
	 * @param IVersionBackend $backend
	 * @since 15.0.0
	 */
	public function registerBackend(string $storageType, IVersionBackend $backend);

	/**
	 * @throws BackendNotFoundException
	 * @since 29.0.0
	 */
	public function getBackendForStorage(IStorage $storage): IVersionBackend;
}

<?php
/**
 * @copyright Copyright (c) 2021 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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

declare(strict_types=1);

namespace OCA\Files_Trashbin\Trash;

use OCP\Files\Storage\IStorage;

/**
 * ITrashBackend extension for the primary storage. This allows additionally to
 * copy the files to the trash.
 */
interface IHomeTrashBackend extends ITrashBackend {
	/**
	 * Copy the file/directory to the trash, but don't delete it.
	 */
	public function copyToTrash(IStorage $storage, string $internalPath): bool;
}

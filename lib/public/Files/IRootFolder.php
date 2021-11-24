<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\Files;

use OC\Hooks\Emitter;
use OC\User\NoUserException;

/**
 * Interface IRootFolder
 *
 * @since 8.0.0
 */
interface IRootFolder extends Folder, Emitter {
	/**
	 * Returns a view to user's files folder
	 *
	 * @param string $userId user ID
	 * @return Folder
	 * @throws NoUserException
	 * @throws NotPermittedException
	 *
	 * @since 8.2.0
	 */
	public function getUserFolder($userId);

	/**
	 * Get a file or folder by fileid, inside a parent path
	 *
	 * @param int $id
	 * @param string $path
	 * @return Node[]
	 *
	 * @since 24.0.0
	 */
	public function getByIdInPath(int $id, string $path);

	/**
	 * Returns a hidden files directory
	 *
	 * This directory can be used to place files hidden for user,
	 * but still usable through most normal api as it is still inside the user folder
	 *
	 * Note that an experienced user can still browser this folder if they manually navigate into it.
	 * Do not rely on it being hidden for security purposes
	 *
	 * @param string $userId user ID
	 * @return \OCP\Files\Folder
	 * @throws NoUserException
	 * @throws NotPermittedException
	 *
	 * @since 24.0.0
	 */
	public function getHiddenUserFolder($userId);
}

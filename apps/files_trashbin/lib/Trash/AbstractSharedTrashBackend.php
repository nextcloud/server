<?php
/**
 * @copyright Copyright (c) 2021 Carl Schwan <carl@carlschwan.eu>
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

namespace OCA\Files_Trashbin\Trash;

use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Trash\IHomeTrashBackend;
use OCA\Files_Trashbin\Trash\ITrashBackend;
use OCA\Files_Trashbin\Trash\ITrashItem;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\Files\Storage\IStorage;
use OC\Files\Storage\W;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;

/**
 * @brief Base class for implementing TrashBackends for shared storage.
 *
 * This will make sure that files that get deleted are also copied to the home
 * storage of the user.
 */
abstract class AbstractSharedTrashBackend implements ITrashBackend {
	/** @var ITrashManager */
	protected $globalTrashManager;

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var IUserSession */
	protected $userSession;

	public function __construct(
		ITrashManager $globalTrashManager,
		IUserSession $userSession,
		IRootFolder $rootFolder
	) {
		$this->globalTrashManager = $globalTrashManager;
		$this->userSession = $userSession;
		$this->rootFolder = $rootFolder;
	}

	public function moveToTrash(IStorage $storage, string $internalPath): bool {
		if ($storage->instanceOfStorage(\OCA\Files_Sharing\SharedStorage::class)) {
			\OC::$server->getLogger()->critical('hej rejirejre oioirej');
			// This is a shared folder so we also do a copy inside the
			// recipient trash otherwise they can't revert their action.
			$homeStorage = $this->rootFolder->getUserFolder($this->userSession->getUser()->getUID())->getStorage();

			try {
				// Make sure that trash backend is available for the storage
				$this->globalTrashManager->getBackendForStorage($homeStorage);
				return \OCA\Files_Trashbin\Trashbin::copy2trash($storage, $internalPath);
			} catch (BackendNotFoundException $e) {
			 	return false;
			}
		}
		return false;
	}
}

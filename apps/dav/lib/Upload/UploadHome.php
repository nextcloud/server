<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\DAV\Upload;

use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use OCP\Constants;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ICollection;

class UploadHome implements ICollection {
	private ?Folder $uploadFolder = null;

	public function __construct(
		private array $principalInfo,
		private CleanupService $cleanupService,
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
		private LoggerInterface $logger,
	) {
	}

	public function createFile($name, $data = null) {
		throw new Forbidden('Permission denied to create file (filename ' . $name . ')');
	}

	public function createDirectory($name) {
		$this->impl()->createDirectory($name);

		// Add a cleanup job
		$this->cleanupService->addJob($name);
	}

	public function getChild($name): UploadFolder {
		return new UploadFolder($this->impl()->getChild($name), $this->cleanupService, $this->getStorage());
	}

	public function getChildren(): array {
		return array_map(function ($node) {
			return new UploadFolder($node, $this->cleanupService, $this->getStorage());
		}, $this->impl()->getChildren());
	}

	public function childExists($name): bool {
		return !is_null($this->getChild($name));
	}

	public function delete() {
		$this->impl()->delete();
	}

	public function getName() {
		[,$name] = \Sabre\Uri\split($this->principalInfo['uri']);
		return $name;
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	public function getLastModified() {
		return $this->impl()->getLastModified();
	}

	private function getUploadFolder(): Folder {
		if ($this->uploadFolder === null) {
			$user = $this->userSession->getUser();
			if (!$user) {
				throw new Forbidden('Not logged in');
			}
			$path = '/' . $user->getUID() . '/uploads';
			try {
				$folder = $this->rootFolder->get($path);
				if (!$folder instanceof Folder) {
					throw new \Exception('Upload folder is a file');
				}
				$this->uploadFolder = $folder;
			} catch (NotFoundException $e) {
				$this->uploadFolder = $this->rootFolder->newFolder($path);
			}
		}
		return $this->uploadFolder;
	}

	private function impl(): Directory {
		$folder = $this->getUploadFolder();
		if (!$folder->isCreatable()) {
			$user = $this->userSession->getUser();
			$this->logger->warning('Upload home not writable for ' . $user->getUID() . ', attempting to fix', ['permissions' => $folder->getPermissions()]);
			$cache = $folder->getStorage()->getCache();
			$cache->update($folder->getId(), ['permissions', Constants::PERMISSION_ALL]);
		}
		$view = new View($folder->getPath());
		return new Directory($view, $folder);
	}

	private function getStorage() {
		return $this->getUploadFolder()->getStorage();
	}
}

<?php
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 */

namespace OC\Collaboration\Reference;

use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\IReferenceProvider;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IURLGenerator;
use OCP\IUserSession;

class FileReferenceProvider implements IReferenceProvider {
	private IURLGenerator $urlGenerator;
	private IRootFolder $rootFolder;
	private ?string $userId;

	public function __construct(IURLGenerator $urlGenerator, IRootFolder $rootFolder, IUserSession $userSession) {
		$this->urlGenerator = $urlGenerator;
		$this->rootFolder = $rootFolder;
		$this->userId = $userSession->getUser() ? $userSession->getUser()->getUID() : null;
	}

	public function matchReference(string $referenceText): bool {
		return str_starts_with($referenceText, $this->urlGenerator->getAbsoluteURL('/index.php/f/'))
			|| str_starts_with($referenceText, $this->urlGenerator->getAbsoluteURL('/f/'));
	}

	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$reference = new Reference($referenceText);
			try {
				$this->fetchReference($reference);
			} catch (NotFoundException $e) {
				$reference->setAccessible(false);
			}
			return $reference;
		}

		return null;
	}

	/**
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\Files\InvalidPathException
	 * @throws NotFoundException
	 * @throws \OC\User\NoUserException
	 */
	private function fetchReference(Reference $reference) {
		if ($this->userId === null) {
			throw new NotFoundException();
		}

		$fileId = str_replace($this->urlGenerator->getAbsoluteURL('/index.php/f/'), '', $reference->getId());
		$fileId = str_replace($this->urlGenerator->getAbsoluteURL('/f/'), '', $fileId);

		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$files = $userFolder->getById((int)$fileId);

		if (empty($files)) {
			throw new NotFoundException();
		}

		/** @var Node $file */
		$file = array_shift($files);

		$reference->setTitle($file->getName());
		$reference->setDescription($file->getMimetype());
		$reference->setUrl($this->urlGenerator->getAbsoluteURL('/index.php/f/' . $fileId));
		$reference->setImageUrl($this->urlGenerator->linkToRouteAbsolute('core.Preview.getPreviewByFileId', ['x' => 1600, 'y' => 630, 'fileId' => $fileId]));

		$reference->setRichObject('file', [
			'id' => $file->getId(),
			'name' => $file->getName(),
			'size' => $file->getSize(),
			'path' => $file->getPath(),
			'link' => $reference->getUrl(),
			'mimetype' => $file->getMimetype(),
			'preview-available' => false
		]);
	}

	public function isGloballyCachable(): bool {
		return false;
	}

	public function getCacheKey(string $referenceId): string {
		return $this->userId;
	}
}

<?php

declare(strict_types=1);
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

namespace OC\Collaboration\Reference\File;

use OC\User\NoUserException;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\Reference;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;

class FileReferenceProvider extends ADiscoverableReferenceProvider {
	private IURLGenerator $urlGenerator;
	private IRootFolder $rootFolder;
	private ?string $userId;
	private IPreview $previewManager;
	private IMimeTypeDetector $mimeTypeDetector;
	private IL10N $l10n;

	public function __construct(
		IURLGenerator $urlGenerator,
		IRootFolder $rootFolder,
		IUserSession $userSession,
		IMimeTypeDetector $mimeTypeDetector,
		IPreview $previewManager,
		IFactory $l10n
	) {
		$this->urlGenerator = $urlGenerator;
		$this->rootFolder = $rootFolder;
		$this->userId = $userSession->getUser() ? $userSession->getUser()->getUID() : null;
		$this->previewManager = $previewManager;
		$this->mimeTypeDetector = $mimeTypeDetector;
		$this->l10n = $l10n->get('files');
	}

	public function matchReference(string $referenceText): bool {
		return $this->getFilesAppLinkId($referenceText) !== null;
	}

	private function getFilesAppLinkId(string $referenceText): ?int {
		$start = $this->urlGenerator->getAbsoluteURL('/apps/files/');
		$startIndex = $this->urlGenerator->getAbsoluteURL('/index.php/apps/files/');

		$fileId = null;

		if (mb_strpos($referenceText, $start) === 0) {
			$parts = parse_url($referenceText);
			parse_str($parts['query'] ?? '', $query);
			$fileId = isset($query['fileid']) ? (int)$query['fileid'] : $fileId;
			$fileId = isset($query['openfile']) ? (int)$query['openfile'] : $fileId;
		}

		if (mb_strpos($referenceText, $startIndex) === 0) {
			$parts = parse_url($referenceText);
			parse_str($parts['query'] ?? '', $query);
			$fileId = isset($query['fileid']) ? (int)$query['fileid'] : $fileId;
			$fileId = isset($query['openfile']) ? (int)$query['openfile'] : $fileId;
		}

		if (mb_strpos($referenceText, $this->urlGenerator->getAbsoluteURL('/index.php/f/')) === 0) {
			$fileId = str_replace($this->urlGenerator->getAbsoluteURL('/index.php/f/'), '', $referenceText);
		}

		if (mb_strpos($referenceText, $this->urlGenerator->getAbsoluteURL('/f/')) === 0) {
			$fileId = str_replace($this->urlGenerator->getAbsoluteURL('/f/'), '', $referenceText);
		}

		return $fileId !== null ? (int)$fileId : null;
	}

	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$reference = new Reference($referenceText);
			try {
				$this->fetchReference($reference);
			} catch (NotFoundException $e) {
				$reference->setRichObject('file', null);
				$reference->setAccessible(false);
			}
			return $reference;
		}

		return null;
	}

	/**
	 * @throws NotFoundException
	 */
	private function fetchReference(Reference $reference): void {
		if ($this->userId === null) {
			throw new NotFoundException();
		}

		$fileId = $this->getFilesAppLinkId($reference->getId());
		if ($fileId === null) {
			throw new NotFoundException();
		}

		try {
			$userFolder = $this->rootFolder->getUserFolder($this->userId);
			$files = $userFolder->getById($fileId);

			if (empty($files)) {
				throw new NotFoundException();
			}

			/** @var Node $file */
			$file = array_shift($files);

			$reference->setTitle($file->getName());
			$reference->setDescription($file->getMimetype());
			$reference->setUrl($this->urlGenerator->getAbsoluteURL('/index.php/f/' . $fileId));
			if ($this->previewManager->isMimeSupported($file->getMimeType())) {
				$reference->setImageUrl($this->urlGenerator->linkToRouteAbsolute('core.Preview.getPreviewByFileId', ['x' => 1600, 'y' => 630, 'fileId' => $fileId]));
			} else {
				$fileTypeIconUrl = $this->mimeTypeDetector->mimeTypeIcon($file->getMimeType());
				$reference->setImageUrl($fileTypeIconUrl);
			}

			$reference->setRichObject('file', [
				'id' => $file->getId(),
				'name' => $file->getName(),
				'size' => $file->getSize(),
				'path' => $userFolder->getRelativePath($file->getPath()),
				'link' => $reference->getUrl(),
				'mimetype' => $file->getMimetype(),
				'mtime' => $file->getMTime(),
				'preview-available' => $this->previewManager->isAvailable($file)
			]);
		} catch (InvalidPathException|NotFoundException|NotPermittedException|NoUserException $e) {
			throw new NotFoundException();
		}
	}

	public function getCachePrefix(string $referenceId): string {
		return (string)$this->getFilesAppLinkId($referenceId);
	}

	public function getCacheKey(string $referenceId): ?string {
		return $this->userId ?? '';
	}

	public function getId(): string {
		return 'files';
	}

	public function getTitle(): string {
		return $this->l10n->t('Files');
	}

	public function getOrder(): int {
		return 0;
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->imagePath('files', 'folder.svg');
	}
}

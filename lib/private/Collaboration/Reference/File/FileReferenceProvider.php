<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Collaboration\Reference\File;

use OC\User\NoUserException;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IPublicReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\Reference;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;

class FileReferenceProvider extends ADiscoverableReferenceProvider implements IPublicReferenceProvider {
	private ?string $userId;
	private IL10N $l10n;

	public function __construct(
		private IURLGenerator $urlGenerator,
		private IRootFolder $rootFolder,
		IUserSession $userSession,
		private IMimeTypeDetector $mimeTypeDetector,
		private IPreview $previewManager,
		IFactory $l10n,
		private ShareManager $shareManager,
	) {
		$this->userId = $userSession->getUser()?->getUID();
		$this->l10n = $l10n->get('files');
	}

	public function matchReference(string $referenceText): bool {
		return $this->getFilesAppLinkId($referenceText) !== null || $this->getFilesAppPublicLinkToken($referenceText) !== null;
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

	private function getFilesAppPublicLinkToken(string $referenceText): ?string {
		$prefixes = ['/index.php/s/', '/s/'];
		
		$token = null;
		
		foreach ($prefixes as $prefix) {
			$fullPrefix = $this->urlGenerator->getAbsoluteURL($prefix);
			if (mb_strpos($referenceText, $fullPrefix) === 0) {
				$token = substr($referenceText, mb_strlen($fullPrefix));
				if (($slashPos = strpos($token, '/')) !== false) {
					$token = substr($token, 0, $slashPos);
				}
				break;
			}
		}
		
		return $token;
	}

	public function resolveReference(string $referenceText): ?IReference {
		$reference = new Reference($referenceText);
		try {
			$fileId = $this->getFilesAppLinkId($referenceText);

			if ($fileId !== null) {
				$this->fetchReference($reference, $fileId);
			} else {
				$fileToken = $this->getFilesAppPublicLinkToken($referenceText);
				if ($fileToken !== null) {
					$this->fetchReferenceForPublicFile($reference, $referenceText, $fileToken);
				} else {
					throw new NotFoundException();
				}
			}
		} catch (NotFoundException $e) {
			$reference->setRichObject('file', null);
			$reference->setAccessible(false);
		}
		return $reference;
	}

	public function resolveReferencePublic(string $referenceText, string $shareToken): ?IReference {
		$reference = new Reference($referenceText);
		try {
			$fileToken = $this->getFilesAppPublicLinkToken($referenceText);
			if ($fileToken !== null) {
				$this->fetchReferenceForPublicFile($reference, $referenceText, $fileToken);
			} else {
				throw new NotFoundException();
			}
		} catch (NotFoundException $e) {
			$reference->setRichObject('file', null);
			$reference->setAccessible(false);
		}
		return $reference;
	}

	private function fetchReferenceForPublicFile(Reference $reference, string $referenceText, string $fileToken): void {
		try {
			$share = $this->shareManager->getShareByToken($fileToken);
			$node = $share->getNode();
			
			$reference->setTitle($node->getName());
			$reference->setDescription($node->getMimetype());
			$reference->setUrl($referenceText);
			if ($this->previewManager->isMimeSupported($node->getMimeType())) {
				$reference->setImageUrl($this->urlGenerator->linkToRouteAbsolute('files_sharing.PublicPreview.getPreview', [
					'x' => 1600,
					'y' => 630,
					'token' => $fileToken
				]));
			} else {
				$fileTypeIconUrl = $this->mimeTypeDetector->mimeTypeIcon($node->getMimeType());
				$reference->setImageUrl($fileTypeIconUrl);
			}
			
			$reference->setRichObject('file', [
				'id' => $fileToken, // security, public link should not show file id
				'name' => $node->getName(),
				'size' => $node->getSize(),
				'path' => $fileToken,
				'link' => $reference->getUrl(),
				'mimetype' => $node->getMimetype(),
				'mtime' => $node->getMTime(),
				'preview-available' => $this->previewManager->isAvailable($node),
				'is-public-link' => true,
			]);
		} catch (ShareNotFound|NotFoundException|InvalidPathException|NotPermittedException $e) {
			$reference->setRichObject('file', null);
			$reference->setAccessible(false);
		}
	}

	/**
	 * @throws NotFoundException
	 */
	private function fetchReference(Reference $reference, int $fileId): void {
		if ($this->userId === null) {
			throw new NotFoundException();
		}

		try {
			$userFolder = $this->rootFolder->getUserFolder($this->userId);
			$file = $userFolder->getFirstNodeById($fileId);

			if (!$file) {
				throw new NotFoundException();
			}

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
		$fileId = $this->getFilesAppLinkId($referenceId);
		if ($fileId !== null) {
			return (string)$fileId;
		} else {
			$fileToken = $this->getFilesAppPublicLinkToken($referenceId);
			if ($fileToken !== null) {
				return $fileToken;
			}
		}
		return '';
	}

	public function getCacheKey(string $referenceId): ?string {
		return $this->userId ?? '';
	}

	public function getCacheKeyPublic(string $referenceId, string $shareToken): ?string {
		return $shareToken;
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

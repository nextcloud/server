<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Collaboration\Reference\File;

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
use OCP\User\Exceptions\UserNotFoundException;

class FileReferenceProvider extends ADiscoverableReferenceProvider implements IPublicReferenceProvider {
	private const PREVIEW_WIDTH = 1600;
	private const PREVIEW_HEIGHT = 630;

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

	#[\Override]
	public function matchReference(string $referenceText): bool {
		return $this->getFilesAppLinkId($referenceText) !== null
			|| $this->getFilesAppPublicLinkToken($referenceText) !== null;
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
		foreach (['/index.php/s/', '/s/'] as $prefix) {
			$fullPrefix = $this->urlGenerator->getAbsoluteURL($prefix);
			if (mb_strpos($referenceText, $fullPrefix) === 0) {
				$token = substr($referenceText, mb_strlen($fullPrefix));
				$separatorPos = strcspn($token, '/?#');
				return substr($token, 0, $separatorPos);
			}
		}

		return null;
	}

	#[\Override]
	public function resolveReference(string $referenceText): ?IReference {
		if (!$this->matchReference($referenceText)) {
			return null;
		}

		$reference = new Reference($referenceText);
		try {
			$fileId = $this->getFilesAppLinkId($referenceText);
			if ($fileId !== null) {
				$this->fetchReference($reference, $fileId);
			} else {
				$fileToken = $this->getFilesAppPublicLinkToken($referenceText);
				if ($fileToken === null) {
					throw new NotFoundException();
				}
				$this->fetchReferenceForPublicFile($reference, $referenceText, $fileToken);
			}
		} catch (NotFoundException $e) {
			$reference->setRichObject('file', null);
			$reference->setAccessible(false);
		}
		return $reference;
	}

	#[\Override]
	public function resolveReferencePublic(string $referenceText, string $sharingToken): ?IReference {
		$reference = new Reference($referenceText);
		$fileToken = $this->getFilesAppPublicLinkToken($referenceText);
		if ($fileToken === null) {
			return null;
		}

		try {
			$this->fetchReferenceForPublicFile($reference, $referenceText, $fileToken);
		} catch (NotFoundException $e) {
			$reference->setRichObject('file', null);
			$reference->setAccessible(false);
		}
		return $reference;
	}

	/**
	 * @throws ShareNotFound if the public share token is invalid so the failed
	 *                       lookup can be counted by rate limiting
	 * @throws NotFoundException if the share exists but the node is gone
	 */
	private function fetchReferenceForPublicFile(Reference $reference, string $referenceText, string $fileToken): void {
		$share = $this->shareManager->getShareByToken($fileToken);

		try {
			$node = $share->getNode();
		} catch (InvalidPathException|NotFoundException|NotPermittedException $e) {
			throw new NotFoundException();
		}

		$reference->setTitle($node->getName());
		$reference->setDescription($node->getMimetype());
		$reference->setUrl($referenceText);
		if ($this->previewManager->isMimeSupported($node->getMimeType())) {
			$reference->setImageUrl($this->urlGenerator->linkToRouteAbsolute(
				'files_sharing.PublicPreview.getPreview',
				['x' => self::PREVIEW_WIDTH, 'y' => self::PREVIEW_HEIGHT, 'token' => $fileToken]));
		} else {
			$fileTypeIconUrl = $this->mimeTypeDetector->mimeTypeIcon($node->getMimeType());
			$reference->setImageUrl($fileTypeIconUrl);
		}

		$reference->setRichObject('file', [
			'id' => $fileToken, // security, public link should not show file id
			'name' => $node->getName(),
			'size' => (string)$node->getSize(),
			'path' => $fileToken,
			'link' => $reference->getUrl(),
			'mimetype' => $node->getMimetype(),
			'mtime' => (string)$node->getMTime(),
			'preview-available' => $this->previewManager->isAvailable($node) ? 'yes' : 'no',
			'is-public-link' => 'yes',
		]);
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
				$reference->setImageUrl($this->urlGenerator->linkToRouteAbsolute(
					'core.Preview.getPreviewByFileId',
					['x' => self::PREVIEW_WIDTH, 'y' => self::PREVIEW_HEIGHT, 'fileId' => $fileId]));
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
		} catch (InvalidPathException|NotFoundException|NotPermittedException|UserNotFoundException $e) {
			throw new NotFoundException();
		}
	}

	#[\Override]
	public function getCachePrefix(string $referenceId): string {
		$fileId = $this->getFilesAppLinkId($referenceId);
		if ($fileId !== null) {
			return (string)$fileId;
		}

		return $this->getFilesAppPublicLinkToken($referenceId) ?? '';
	}

	#[\Override]
	public function getCacheKey(string $referenceId): ?string {
		return $this->userId ?? '';
	}

	#[\Override]
	public function getCacheKeyPublic(string $referenceId, string $sharingToken): ?string {
		return $sharingToken;
	}

	#[\Override]
	public function getId(): string {
		return 'files';
	}

	#[\Override]
	public function getTitle(): string {
		return $this->l10n->t('Files');
	}

	#[\Override]
	public function getOrder(): int {
		return 0;
	}

	#[\Override]
	public function getIconUrl(): string {
		return $this->urlGenerator->imagePath('files', 'folder.svg');
	}
}

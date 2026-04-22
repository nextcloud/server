<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Reference;

use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\Reference;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;

class ShareReferenceProvider extends ADiscoverableReferenceProvider {

	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ShareManager $shareManager,
		private IMimeTypeDetector $mimeTypeDetector,
		private IPreview $previewManager,
	) {
	}

	public function matchReference(string $referenceText): bool {
		return $this->getShareToken($referenceText) !== null;
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

	public function getCachePrefix(string $referenceId): string {
		$token = $this->getShareToken($referenceId);
		return $token !== null ? $token : '';
	}

	public function getCacheKey(string $referenceId): ?string {
		// Cache key depends on the share token
		return $this->getCachePrefix($referenceId);
	}

	public function getId(): string {
		return 'files_sharing';
	}

	public function getTitle(): string {
		return $this->l10n->t('Shared files');
	}

	public function getOrder(): int {
		return 5;
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->imagePath('files_sharing', 'app.svg');
	}

	private function getShareToken(string $referenceText): ?string {
		$start = $this->urlGenerator->getAbsoluteURL('/s/');
		$startIndex = $this->urlGenerator->getAbsoluteURL('/index.php/s/');

		if (mb_strpos($referenceText, $start) === 0) {
			return $this->extractToken($referenceText, $start);
		}

		if (mb_strpos($referenceText, $startIndex) === 0) {
			return $this->extractToken($referenceText, $startIndex);
		}

		return null;
	}

	private function extractToken(string $referenceText, string $prefix): ?string {
		$token = mb_substr($referenceText, mb_strlen($prefix));
		// Token might be followed by query parameters or path
		$parts = parse_url($referenceText);
		if (isset($parts['path'])) {
			$token = mb_substr($parts['path'], mb_strlen(parse_url($prefix, PHP_URL_PATH)));
		}
		// Remove any trailing slash or additional path components
		$token = strtok($token, '/');
		return $token !== '' ? $token : null;
	}

	/**
	 * @throws NotFoundException
	 */
	private function fetchReference(Reference $reference): void {
		$token = $this->getShareToken($reference->getId());
		if ($token === null) {
			throw new NotFoundException();
		}

		try {
			$share = $this->shareManager->getShareByToken($token);
			$node = $share->getNode();

			$reference->setTitle($node->getName());
			$reference->setDescription($node->getMimetype());
			$reference->setUrl($this->urlGenerator->getAbsoluteURL('/s/' . $token));

			if ($this->previewManager->isMimeSupported($node->getMimeType())) {
				$reference->setImageUrl($this->urlGenerator->linkToRouteAbsolute('core.Preview.getPreviewByFileId', [
					'x' => 1600,
					'y' => 630,
					'fileId' => $node->getId(),
				]));
			} else {
				$fileTypeIconUrl = $this->mimeTypeDetector->mimeTypeIcon($node->getMimeType());
				$reference->setImageUrl($fileTypeIconUrl);
			}

			$reference->setRichObject('file', [
				'id' => $node->getId(),
				'name' => $node->getName(),
				'size' => $node->getSize(),
				'path' => $node->getPath(),
				'link' => $reference->getUrl(),
				'mimetype' => $node->getMimetype(),
				'mtime' => $node->getMTime(),
				'preview-available' => $this->previewManager->isAvailable($node),
				'share-token' => $token,
			]);
		} catch (ShareNotFound|InvalidPathException|NotFoundException|NotPermittedException $e) {
			throw new NotFoundException(previous: $e);
		}
	}
}

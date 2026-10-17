<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Reference;

use OCP\Collaboration\Reference\IPublicReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\Reference;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;

/**
 * Resolves public share links (/s/{token}) into file references so they can be
 * embedded as widgets, including on public pages where no user is logged in.
 */
class PublicShareReferenceProvider implements IPublicReferenceProvider {
	private const PREVIEW_WIDTH = 1600;
	private const PREVIEW_HEIGHT = 630;

	public function __construct(
		private IURLGenerator $urlGenerator,
		private IMimeTypeDetector $mimeTypeDetector,
		private IPreview $previewManager,
		private ShareManager $shareManager,
	) {
	}

	#[\Override]
	public function matchReference(string $referenceText): bool {
		return $this->getPublicLinkToken($referenceText) !== null;
	}

	private function getPublicLinkToken(string $referenceText): ?string {
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
		return $this->resolve($referenceText);
	}

	#[\Override]
	public function resolveReferencePublic(string $referenceText, string $sharingToken): ?IReference {
		return $this->resolve($referenceText);
	}

	private function resolve(string $referenceText): ?IReference {
		$fileToken = $this->getPublicLinkToken($referenceText);
		if ($fileToken === null) {
			return null;
		}

		$reference = new Reference($referenceText);
		try {
			$this->fetchReference($reference, $referenceText, $fileToken);
		} catch (NotFoundException) {
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
	private function fetchReference(Reference $reference, string $referenceText, string $fileToken): void {
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

	#[\Override]
	public function getCachePrefix(string $referenceId): string {
		return $this->getPublicLinkToken($referenceId) ?? '';
	}

	#[\Override]
	public function getCacheKey(string $referenceId): ?string {
		// The resolution result only depends on the share token
		return null;
	}

	#[\Override]
	public function getCacheKeyPublic(string $referenceId, string $sharingToken): ?string {
		return $sharingToken;
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Files\Sharing;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Upload\UploadFolder;
use OCA\DAV\Upload\UploadHome;
use OCP\Files\IMimeTypeDetector;
use OCP\Share\IShare;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\UnsupportedMediaType;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\Uri;

/**
 * Restrict the file types that can be uploaded to a public share.
 *
 * The allowed types are read from the `upload.allowed_types` share attribute
 * and use the format of the HTML `accept` attribute: file extensions (`.pdf`),
 * MIME types (`application/pdf`) or MIME type groups (`image/*`).
 */
class AllowedFileTypesPlugin extends ServerPlugin {

	private ?IShare $share = null;
	private Server $server;

	public function __construct(
		private readonly IMimeTypeDetector $mimeTypeDetector,
	) {
	}

	public function setShare(IShare $share): void {
		$this->share = $share;
	}

	#[\Override]
	public function initialize(Server $server): void {
		$this->server = $server;
		$server->on('beforeMethod:MKCOL', [$this, 'beforeMkcol']);
		$server->on('beforeBind', [$this, 'beforeBind']);
	}

	/**
	 * Check the target file of a chunked upload before any chunk is sent
	 *
	 * @throws UnsupportedMediaType if the file type is not allowed
	 */
	public function beforeMkcol(RequestInterface $request): bool {
		$allowedTypes = $this->getAllowedTypes();
		$destination = $request->getHeader('Destination');
		if ($allowedTypes === [] || $destination === null || !$this->isChildOf($request->getPath(), UploadHome::class)) {
			return true;
		}

		$this->checkFileType($this->server->calculateUri($destination), $allowedTypes);
		return true;
	}

	/**
	 * @throws UnsupportedMediaType if the file type is not allowed
	 * @throws Forbidden if a collection outside of the share is moved or copied
	 */
	public function beforeBind(string $path): bool {
		$allowedTypes = $this->getAllowedTypes();
		// Chunks are only checked once they are assembled into the target file
		if ($allowedTypes === [] || $this->isChildOf($path, UploadFolder::class)) {
			return true;
		}

		$sourceNode = $this->getSourceNode();
		if ($sourceNode instanceof ICollection) {
			// Other collections, like upload folders, can contain files that were never checked
			if (!$sourceNode instanceof Directory) {
				throw new Forbidden('Only folders of the share can be moved or copied');
			}
			return true;
		}

		if ($this->server->httpRequest->getMethod() === 'MKCOL') {
			return true;
		}

		$this->checkFileType($path, $allowedTypes);
		return true;
	}

	/**
	 * @param list<string> $allowedTypes
	 * @throws UnsupportedMediaType if the file type is not allowed
	 */
	private function checkFileType(string $path, array $allowedTypes): void {
		[, $fileName] = Uri\split($path);
		if (!$this->isAllowed($fileName, $allowedTypes)) {
			throw new UnsupportedMediaType('File type not allowed: ' . $fileName);
		}
	}

	/**
	 * @return list<string>
	 */
	private function getAllowedTypes(): array {
		$allowedTypes = $this->share?->getAttributes()?->getAttribute('upload', 'allowed_types');
		if (!is_array($allowedTypes)) {
			return [];
		}

		$allowedTypes = array_map(
			static fn (mixed $type): string => is_string($type) ? strtolower(trim($type)) : '',
			$allowedTypes,
		);
		return array_values(array_filter($allowedTypes, static fn (string $type): bool => $type !== ''));
	}

	/**
	 * Source node of a MOVE or COPY request
	 */
	private function getSourceNode(): ?INode {
		$request = $this->server->httpRequest;
		if ($request->getMethod() !== 'MOVE' && $request->getMethod() !== 'COPY') {
			return null;
		}

		try {
			return $this->server->tree->getNodeForPath($request->getPath());
		} catch (NotFound) {
			return null;
		}
	}

	/**
	 * @param class-string $collectionClass
	 */
	private function isChildOf(string $path, string $collectionClass): bool {
		[$parentPath] = Uri\split($path);
		try {
			return $this->server->tree->getNodeForPath($parentPath) instanceof $collectionClass;
		} catch (NotFound) {
			return false;
		}
	}

	/**
	 * @param list<string> $allowedTypes
	 */
	private function isAllowed(string $fileName, array $allowedTypes): bool {
		$fileName = strtolower($fileName);
		$mimeType = $this->mimeTypeDetector->detectPath($fileName);

		foreach ($allowedTypes as $allowedType) {
			if (str_starts_with($allowedType, '.')) {
				$matches = str_ends_with($fileName, $allowedType);
			} elseif (str_ends_with($allowedType, '/*')) {
				$matches = str_starts_with($mimeType, substr($allowedType, 0, -1));
			} else {
				$matches = $mimeType === $allowedType;
			}

			if ($matches) {
				return true;
			}
		}

		return false;
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Sabre;

use OC\Http\ContentDisposition;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCP\IPreview;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * SabreDAV plugin for managing versioned file access and metadata.
 *
 * Handles WebDAV requests related to file versions, including download headers,
 * version metadata properties, and compatibility for various clients and browsers.
 */
class Plugin extends ServerPlugin {
	public const LABEL = 'label';
	public const AUTHOR = 'author';
	public const VERSION_LABEL = '{http://nextcloud.org/ns}version-label';
	public const VERSION_AUTHOR = '{http://nextcloud.org/ns}version-author';

	private Server $server;

	public function __construct(
		private readonly IPreview $previewManager,
	) {
	}

	public function initialize(Server $server): void {
		$this->server = $server;

		$server->on('afterMethod:GET', [$this, 'afterGet']);
		$server->on('propFind', [$this, 'propFind']);
		$server->on('propPatch', [$this, 'propPatch']);
	}

	/**
	 * Handles the GET request for versioned files.
	 *
	 * Validates the request path, checks node type, and sets appropriate download headers
	 * to ensure compatibility across different clients and browsers.
	 */
	public function afterGet(RequestInterface $request, ResponseInterface $response): void {
		$path = $request->getPath();

		if (!str_starts_with($path, 'versions/')) {
			return;
		}

		try {
			$node = $this->server->tree->getNodeForPath($path);
		} catch (NotFound $e) {
			return;
		}

		if (!($node instanceof VersionFile)) {
			return;
		}

		$filename = $node->getVersion()->getSourceFileName();
		$response->addHeader('Content-Disposition', ContentDisposition::make('attachment', $filename));
	}

	/**
	 * WebDAV PROPFIND event handler for versioned files.
	 *
	 * Provides read-only access to version-related information if the
	 * current node is a VersionFile.
	 */
	public function propFind(PropFind $propFind, INode $node): void {
		if (!($node instanceof VersionFile)) {
			return;
		}

		$propFind->handle(
			self::VERSION_LABEL,
			fn () => $node->getMetadataValue(self::LABEL)
		);
		$propFind->handle(
			self::VERSION_AUTHOR,
			fn () => $node->getMetadataValue(self::AUTHOR)
		);
		$propFind->handle(
			FilesPlugin::HAS_PREVIEW_PROPERTYNAME,
			fn (): string => $this->previewManager->isMimeSupported($node->getContentType()) ? 'true' : 'false',
		);
	}

	/**
	 * WebDAV PROPPATCH event handler for versioned files.
	 *
	 * Updates version related properties on VersionFile nodes.
	 */
	public function propPatch(string $path, PropPatch $propPatch): void {
		$node = $this->server->tree->getNodeForPath($path);

		if (!($node instanceof VersionFile)) {
			return;
		}

		$propPatch->handle(
			self::VERSION_LABEL,
			fn (string $label) => $node->setMetadataValue(self::LABEL, $label)
		);
	}
}

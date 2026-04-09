<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Sabre;

use OC\AppFramework\Http\Request;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCP\IPreview;
use OCP\IRequest;
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
	private const LEGACY_FILENAME_HEADER_USER_AGENTS = [ // Quirky clients
		Request::USER_AGENT_IE,
		Request::USER_AGENT_ANDROID_MOBILE_CHROME,
		Request::USER_AGENT_FREEBOX,
	];
	private Server $server;

	public function __construct(
		private readonly IRequest $request,
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
		$this->addContentDispositionHeader($response, $filename);
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

	/**
	 * Add a Content-Disposition header in a way that attempts to be broadly compatible with various user agents.
	 *
	 * Sends both 'filename' (legacy quoted) and 'filename*' (UTF-8 encoded) per RFC 6266,
	 * except for known quirky agents known to mishandle the `filename*`, which only get `filename`.
	 *
	 * Note: The quoting/escaping should strictly follow RFC 6266 and RFC 5987.
	 *
	 * TODO: Currently uses rawurlencode($filename) for both parameters, which is wrong: filename= should be plain
	 * quoted ASCII (with necessary escaping), while filename* should be UTF-8 percent-encoded.
	 * TODO: This logic appears elsewhere (sometimes with different quoting/filename handling) and could benefit
	 * from a shared utility function. See Symfony example:
	 * - https://github.com/symfony/symfony/blob/175775eb21508becf7e7a16d65959488e522c39a/src/Symfony/Component/HttpFoundation/BinaryFileResponse.php#L146-L155
	 * - https://github.com/symfony/symfony/blob/175775eb21508becf7e7a16d65959488e522c39a/src/Symfony/Component/HttpFoundation/HeaderUtils.php#L152-L165
	 *
	 * @param ResponseInterface $response HTTP response object to add the header to
	 * @param string $filename Download filename
	 */
	private function addContentDispositionHeader(ResponseInterface $response, string $filename): void {
		if (!$this->request->isUserAgent(self::LEGACY_FILENAME_HEADER_USER_AGENTS)) {
			// Modern clients will use 'filename*'; older clients will refer to `filename`.
			// The older fallback must be listed first per RFC.
			// In theory this is all we actually need to handle both client types.
			$response->addHeader(
				'Content-Disposition',
				'attachment; filename="' . rawurlencode($filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename)
			);
		} else {
			// Quirky clients that choke on `filename*`: only send `filename=`
			$response->addHeader(
				'Content-Disposition',
				'attachment; filename="' . rawurlencode($filename) . '"');
		}
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Connector\Sabre;

use OC\Streamer;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\BeforeZipCreatedEvent;
use OCP\Files\File as NcFile;
use OCP\Files\Folder as NcFolder;
use OCP\Files\Node as NcNode;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Tree;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

/**
 * This plugin allows to download folders accessed by GET HTTP requests on DAV.
 * The WebDAV standard explicitly say that GET is not covered and should return what ever the application thinks would be a good representation.
 *
 * When a collection is accessed using GET, this will provide the content as a archive.
 * The type can be set by the `Accept` header (MIME type of zip or tar), or as browser fallback using a `accept` GET parameter.
 * It is also possible to only include some child nodes (from the collection it self) by providing a `filter` GET parameter or `X-NC-Files` custom header.
 */
class ZipFolderPlugin extends ServerPlugin {

	/**
	 * Reference to main server object
	 */
	private ?Server $server = null;

	public function __construct(
		private Tree $tree,
		private LoggerInterface $logger,
		private IEventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 */
	public function initialize(Server $server): void {
		$this->server = $server;
		$this->server->on('method:GET', $this->handleDownload(...), 100);
	}

	/**
	 * Adding a node to the archive streamer.
	 * This will recursively add new nodes to the stream if the node is a directory.
	 */
	protected function streamNode(Streamer $streamer, NcNode $node, string $rootPath): void {
		// Remove the root path from the filename to make it relative to the requested folder
		$filename = str_replace($rootPath, '', $node->getPath());

		if ($node instanceof NcFile) {
			$resource = $node->fopen('rb');
			if ($resource === false) {
				$this->logger->info('Cannot read file for zip stream', ['filePath' => $node->getPath()]);
				throw new \Sabre\DAV\Exception\ServiceUnavailable('Requested file can currently not be accessed.');
			}
			$streamer->addFileFromStream($resource, $filename, $node->getSize(), $node->getMTime());
		} elseif ($node instanceof NcFolder) {
			$streamer->addEmptyDir($filename);
			$content = $node->getDirectoryListing();
			foreach ($content as $subNode) {
				$this->streamNode($streamer, $subNode, $rootPath);
			}
		}
	}

	/**
	 * Download a folder as an archive.
	 * It is possible to filter / limit the files that should be downloaded,
	 * either by passing (multiple) `X-NC-Files: the-file` headers
	 * or by setting a `files=JSON_ARRAY_OF_FILES` URL query.
	 *
	 * @return false|null
	 */
	public function handleDownload(Request $request, Response $response): ?bool {
		$node = $this->tree->getNodeForPath($request->getPath());
		if (!($node instanceof \OCA\DAV\Connector\Sabre\Directory)) {
			// only handle directories
			return null;
		}

		$query = $request->getQueryParameters();

		// Get accept header - or if set overwrite with accept GET-param
		$accept = $request->getHeaderAsArray('Accept');
		$acceptParam = $query['accept'] ?? '';
		if ($acceptParam !== '') {
			$accept = array_map(fn (string $name) => strtolower(trim($name)), explode(',', $acceptParam));
		}
		$zipRequest = !empty(array_intersect(['application/zip', 'zip'], $accept));
		$tarRequest = !empty(array_intersect(['application/x-tar', 'tar'], $accept));
		if (!$zipRequest && !$tarRequest) {
			// does not accept zip or tar stream
			return null;
		}

		$files = $request->getHeaderAsArray('X-NC-Files');
		$filesParam = $query['files'] ?? '';
		// The preferred way would be headers, but this is not possible for simple browser requests ("links")
		// so we also need to support GET parameters
		if ($filesParam !== '') {
			$files = json_decode($filesParam);
			if (!is_array($files)) {
				$files = [$files];
			}

			foreach ($files as $file) {
				if (!is_string($file)) {
					// we log this as this means either we - or an app - have a bug somewhere or a user is trying invalid things
					$this->logger->notice('Invalid files filter parameter for ZipFolderPlugin', ['filter' => $filesParam]);
					// no valid parameter so continue with Sabre behavior
					return null;
				}
			}
		}

		$folder = $node->getNode();
		$event = new BeforeZipCreatedEvent($folder, $files);
		$this->eventDispatcher->dispatchTyped($event);
		if ((!$event->isSuccessful()) || $event->getErrorMessage() !== null) {
			$errorMessage = $event->getErrorMessage();
			if ($errorMessage === null) {
				// Not allowed to download but also no explaining error
				// so we abort the ZIP creation and fall back to Sabre default behavior.
				return null;
			}
			// Downloading was denied by an app
			throw new Forbidden($errorMessage);
		}

		$content = empty($files) ? $folder->getDirectoryListing() : [];
		foreach ($files as $path) {
			$child = $node->getChild($path);
			assert($child instanceof Node);
			$content[] = $child->getNode();
		}

		$archiveName = 'download';
		$rootPath = $folder->getPath();
		if (empty($files)) {
			// We download the full folder so keep it in the tree
			$rootPath = dirname($folder->getPath());
			// Full folder is loaded to rename the archive to the folder name
			$archiveName = $folder->getName();
		}
		$streamer = new Streamer($tarRequest, -1, count($content));
		$streamer->sendHeaders($archiveName);
		// For full folder downloads we also add the folder itself to the archive
		if (empty($files)) {
			$streamer->addEmptyDir($archiveName);
		}
		foreach ($content as $node) {
			$this->streamNode($streamer, $node, $rootPath);
		}
		$streamer->finalize();
		return false;
	}
}

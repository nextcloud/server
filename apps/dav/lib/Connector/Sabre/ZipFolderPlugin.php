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
use OCP\IDateTimeZone;
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
		private IDateTimeZone $timezoneFactory,
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
		// low priority to give any other afterMethod:* a chance to fire before we cancel everything
		$this->server->on('afterMethod:GET', $this->afterDownload(...), 999);
	}

	/**
	 * Recursively iterate over all nodes in a folder.
	 */
	protected function iterateNodes(NcNode $node): iterable {
		if ($node instanceof NcFile) {
			yield $node;
		} elseif ($node instanceof NcFolder) {
			yield $node;
			foreach ($node->getDirectoryListing() as $childNode) {
				yield from $this->iterateNodes($childNode);
			}
		}
	}

	/**
	 * Adding a node to the archive streamer.
	 */
	protected function streamNode(Streamer $streamer, NcNode $node, string $rootPath): void {
		// Remove the root path from the filename to make it relative to the requested folder
		$filename = str_replace($rootPath, '', $node->getPath());

		$mtime = $node->getMTime();
		if ($node instanceof NcFile) {
			$resource = $node->fopen('rb');
			if ($resource === false) {
				$this->logger->info('Cannot read file for zip stream', ['filePath' => $node->getPath()]);
				throw new \Sabre\DAV\Exception\ServiceUnavailable('Requested file can currently not be accessed.');
			}
			$streamer->addFileFromStream($resource, $filename, $node->getSize(), $mtime);
		} elseif ($node instanceof NcFolder) {
			$streamer->addEmptyDir($filename, $mtime);
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
		if (!($node instanceof Directory)) {
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
		$rootNodes = empty($files) ? $folder->getDirectoryListing() : [];
		foreach ($files as $path) {
			$child = $node->getChild($path);
			assert($child instanceof Node);
			$rootNodes[] = $child->getNode();
		}
		$allNodes = [];
		foreach ($rootNodes as $rootNode) {
			foreach ($this->iterateNodes($rootNode) as $node) {
				$allNodes[] = $node;
			}
		}

		$event = new BeforeZipCreatedEvent($folder, $files, $allNodes);
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
		$allNodes = $event->getNodes();

		$archiveName = $folder->getName();
		if (count(explode('/', trim($folder->getPath(), '/'), 3)) === 2) {
			// this is a download of the root folder
			$archiveName = 'download';
		}

		$rootPath = $folder->getPath();
		if (empty($files)) {
			// We download the full folder so keep it in the tree
			$rootPath = dirname($folder->getPath());
		}

		$streamer = new Streamer($tarRequest, -1, count($rootNodes), $this->timezoneFactory);
		$streamer->sendHeaders($archiveName);
		// For full folder downloads we also add the folder itself to the archive
		if (empty($files)) {
			$streamer->addEmptyDir($archiveName);
		}
		foreach ($allNodes as $node) {
			$this->streamNode($streamer, $node, $rootPath);
		}
		$streamer->finalize();
		return false;
	}

	/**
	 * Tell sabre/dav not to trigger it's own response sending logic as the handleDownload will have already send the response
	 *
	 * @return false|null
	 */
	public function afterDownload(Request $request, Response $response): ?bool {
		$node = $this->tree->getNodeForPath($request->getPath());
		if (!($node instanceof Directory)) {
			// only handle directories
			return null;
		} else {
			return false;
		}
	}
}

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
use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\IL10N;
use OCP\L10N\IFactory;
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
	private bool $reportMissingFiles;
	private array $missingInfo = [];
	private IL10N $l10n;

	public function __construct(
		private Tree $tree,
		private LoggerInterface $logger,
		private IEventDispatcher $eventDispatcher,
		private IDateTimeZone $timezoneFactory,
		private IConfig $config,
		private IFactory $l10nFactory,
	) {
		$this->reportMissingFiles = $this->config->getSystemValueBool('archive_report_missing_files', false);

		if ($this->reportMissingFiles) {
			stream_filter_register('count.bytes', ByteCounterFilter::class);
		}

		$this->l10n = $this->l10nFactory->get('dav');
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
	 * Adding a node to the archive streamer.
	 * @return ?string an error message if an error occurred and reporting is enabled, null otherwise
	 */
	protected function streamNode(Streamer $streamer, NcNode $node, string $rootPath): ?string {
		// Remove the root path from the filename to make it relative to the requested folder
		$filename = str_replace($rootPath, '', $node->getPath());

		$mtime = $node->getMTime();
		if ($node instanceof NcFolder) {
			$streamer->addEmptyDir($filename, $mtime);
			return null;
		}

		if ($node instanceof NcFile) {
			$nodeSize = $node->getSize();
			try {
				$stream = $node->fopen('rb');
			} catch (\Exception $e) {
				// opening failed, log the failure as reason for the missing file
				if ($this->reportMissingFiles) {
					$exceptionClass = get_class($e);
					return $this->l10n->t('Error while opening the file: %s', [$exceptionClass]);
				}

				throw $e;
			}

			if ($this->reportMissingFiles) {
				if ($stream === false) {
					return $this->l10n->t('File could not be opened (fopen). Please check the server logs for more information.');
				}

				$byteCounter = new StreamByteCounter();
				$wrapped = stream_filter_append($stream, 'count.bytes', STREAM_FILTER_READ, ['counter' => $byteCounter]);
				if ($wrapped === false) {
					return $this->l10n->t('Unable to check file for consistency check');
				}
			}

			$fileAddedToStream = $streamer->addFileFromStream($stream, $filename, $nodeSize, $mtime);
			if ($this->reportMissingFiles) {
				if (!$fileAddedToStream) {
					return $this->l10n->t('The archive was already finalized');
				}

				return $this->logStreamErrors($stream, $filename, $nodeSize, $byteCounter->bytes);
			}

			return null;
		}
	}

	/**
	 * Checks whether $stream was fully streamed or if there were other issues
	 * with the stream, logging the error if necessary.
	 *
	 */
	private function logStreamErrors(mixed $stream, string $path, float|int $expectedFileSize, float|int $readFileSize): ?string {
		$streamMetadata = stream_get_meta_data($stream);
		if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
			return $this->l10n->t('Resource is not a stream or is closed.');
		}

		if ($streamMetadata['timed_out'] ?? false) {
			return $this->l10n->t('Timeout while reading from stream.');
		}

		if (!($streamMetadata['eof'] ?? true) || $readFileSize != $expectedFileSize) {
			return $this->l10n->t('Read %d out of %d bytes from storage. This means the connection may have been closed due to a network/storage error.', [$expectedFileSize, $readFileSize]);
		}

		return null;
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
		$event = new BeforeZipCreatedEvent($folder, $files, $this->reportMissingFiles);
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

		// At this point either the event handlers did not block the download
		// or they support the new mechanism that filters out nodes that are not
		// downloadable, in either case we can use the new API to set the iterator
		$content = empty($files) ? $folder->getDirectoryListing() : [];
		foreach ($files as $path) {
			$child = $node->getChild($path);
			assert($child instanceof Node);
			$content[] = $child->getNode();
		}
		$event->setNodesIterable($this->getIterableFromNodes($content));

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

		// numberOfFiles is irrelevant as size=-1 forces the use of zip64 already
		$streamer = new Streamer($tarRequest, -1, 0, $this->timezoneFactory);
		$streamer->sendHeaders($archiveName);
		// For full folder downloads we also add the folder itself to the archive
		if (empty($files)) {
			$streamer->addEmptyDir($archiveName);
		}

		foreach ($event->getNodes() as $path => [$node, $reason]) {
			$filename = str_replace($rootPath, '', $path);
			if ($node === null) {
				if ($this->reportMissingFiles) {
					$this->missingInfo[$filename] = $reason;
				}
				continue;
			}

			$streamError = $this->streamNode($streamer, $node, $rootPath);
			if ($this->reportMissingFiles && $streamError !== null) {
				$this->missingInfo[$filename] = $streamError;
			}
		}

		if ($this->reportMissingFiles && !empty($this->missingInfo)) {
			$json = json_encode($this->missingInfo, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			$stream = fopen('php://temp', 'r+');
			fwrite($stream, $json);
			rewind($stream);
			$streamer->addFileFromStream($stream, 'missing_files.json', (float)strlen($json), false);
		}
		$streamer->finalize();
		return false;
	}

	/**
	 * Given a set of nodes, produces a list of all nodes contained in them
	 * recursively.
	 *
	 * @param NcNode[] $nodes
	 * @return iterable<NcNode>
	 */
	private function getIterableFromNodes(array $nodes): iterable {
		foreach ($nodes as $node) {
			yield $node;

			if ($node instanceof NcFolder) {
				foreach ($node->getDirectoryListing() as $child) {
					yield from $this->getIterableFromNodes([$child]);
				}
			}
		}
	}

	/**
	 * Tell sabre/dav not to trigger its own response sending logic as the handleDownload will have already send the response
	 *
	 * @return false|null
	 */
	public function afterDownload(Request $request, Response $response): ?bool {
		$node = $this->tree->getNodeForPath($request->getPath());
		if ($node instanceof Directory) {
			// only handle directories
			return false;
		}

		return null;
	}
}

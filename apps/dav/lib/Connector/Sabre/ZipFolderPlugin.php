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
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\IL10N;
use OCP\Lock\LockedException;
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
	private bool $tarArchive = false;

	public const string MISSING_FILES_FILENAME = 'missing_files';
	public const string MISSING_FILES_EXTENSION = '.json';
	public const string MISSING_FILES_FULL_FILENAME = self::MISSING_FILES_FILENAME . self::MISSING_FILES_EXTENSION;

	/**
	 * Whether handleDownload has fully streamed an archive for the current request.
	 * Used by afterDownload to decide whether to suppress sabre/dav's own response logic.
	 */
	private bool $streamed = false;

	public function __construct(
		private Tree $tree,
		private LoggerInterface $logger,
		private IEventDispatcher $eventDispatcher,
		private IDateTimeZone $timezoneFactory,
		private IConfig $config,
		private IL10N $l10n,
	) {
		$this->reportMissingFiles = $this->config->getSystemValueBool('archive_report_missing_files', true);
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 */
	#[\Override]
	public function initialize(Server $server): void {
		$this->server = $server;
		$this->server->on('method:GET', $this->handleDownload(...), 100);
		// low priority to give any other afterMethod:* a chance to fire before we cancel everything
		$this->server->on('afterMethod:GET', $this->afterDownload(...), 999);
	}

	/**
	 * Adding a node to the archive streamer.
	 * @return ?string an error message if an error occurred and reporting is enabled, null otherwise
	 * @throws NotPermittedException|LockedException
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

			$source = $node->fopen('rb');
			if ($source === false) {
				return $this->l10n->t('File could not be opened (fopen). Please check the server logs for more information.');
			}
			// get the size after fopen, so the file is locked
			$nodeSize = $node->getSize();

			$entry = null;
			$stream = ArchiveEntryStream::wrap($source, $nodeSize, $this->tarArchive, static function (ArchiveEntryStream $closed) use (&$entry): void {
				$entry = $closed;
			});

			if ($stream === false) {
				fclose($source);
				return $this->l10n->t('Unable to check file for consistency check');
			}

			try {
				// todo: if tar archive, ask the storage for the size, so outdated files are never truncated or 0-padded
				$fileAddedToStream = $streamer->addFileFromStream($stream, $filename, $nodeSize, $mtime);
			} finally {
				// also closes $source and passes the final read state to the callback
				fclose($stream);
			}
			assert($entry instanceof ArchiveEntryStream);

			if (!$fileAddedToStream) {
				return $this->l10n->t('The archive was already finalized, stream was not a stream or was closed');
			}

			$throwable = $entry->getThrowable();
			if ($throwable !== null) {
				$this->logger->error('Reading the file failed while adding it to the archive', ['exception' => $throwable, 'path' => $node->getPath()]);
			}

			$bytesRead = $entry->getBytesRead();
			if ($entry->hasFailed() || $bytesRead < $nodeSize) {
				$message = $entry->hasFailed()
					? $this->l10n->t('Reading the file failed after %1$d of %2$d bytes.', [$bytesRead, $nodeSize])
					: $this->l10n->t('Read %d out of %d bytes from storage. This means the connection may have been closed due to a network/storage error.', [$bytesRead, $nodeSize]);
				if ($this->tarArchive) {
					$message .= ' ' . $this->l10n->t('The missing part of the file was filled with empty bytes.');
				}
				return $message;
			}

			if ($entry->isTruncated()) {
				return $this->l10n->t('The file is larger than its recorded size, only the first %d bytes were added.', [$nodeSize]);
			}
		}

		return null;
	}

	/**
	 * Download a folder as an archive.
	 * It is possible to filter / limit the files that should be downloaded,
	 * either by passing (multiple) `X-NC-Files: the-file` headers
	 * or by setting a `files=JSON_ARRAY_OF_FILES` URL query.
	 */
	public function handleDownload(Request $request, Response $response): ?false {
		if ($request->getHeader('X-Sabre-Original-Method') === 'HEAD') {
			return null;
		}
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

		$this->tarArchive = $tarRequest;
		$streamer = new Streamer($tarRequest, -1, -1, $this->timezoneFactory);
		$streamer->sendHeaders($archiveName);
		// For full folder downloads we also add the folder itself to the archive
		if (empty($files)) {
			$streamer->addEmptyDir($archiveName);
		}

		$content = empty($files) ? $folder->getDirectoryListing() : array_map(fn (string $path) => $folder->get($path), $files);
		foreach ($content as $node) {
			assert($node instanceof NcNode);
			$this->streamTree($streamer, $event, $node, $rootPath);
		}

		if ($this->reportMissingFiles && !empty($this->missingInfo)) {
			$json = json_encode($this->missingInfo, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);

			$missingFilesFileName = self::MISSING_FILES_FULL_FILENAME;
			if ($files !== []) {
				$names = array_map(static fn (NcNode $node): string => $node->getName(), $content);
				// only when filtering there is the risk of a conflict
				for ($i = 1; in_array($missingFilesFileName, $names, true); $i++) {
					$missingFilesFileName = self::MISSING_FILES_FILENAME . " ($i)" . self::MISSING_FILES_EXTENSION;
				}
			}
			if ($json !== false) {
				$stream = fopen('php://temp', 'r+');
				fwrite($stream, $json);
				rewind($stream);
				$streamer->addFileFromStream($stream, $missingFilesFileName, (float)strlen($json), false);
			} else {
				$this->logger->error("Error while generating {$missingFilesFileName} when generating archive for '$archiveName', error: " . json_last_error_msg());
			}
		}
		$streamer->finalize();
		$this->streamed = true; // archive fully streamed

		return false;
	}

	/**
	 * Adds the node and, for folders, its content to the archive, skipping
	 * nodes excluded by the event's node filters.
	 *
	 * @throws \Exception if adding a node fails and missing files are not reported
	 */
	private function streamTree(Streamer $streamer, BeforeZipCreatedEvent $event, NcNode $node, string $rootPath): void {
		$filename = ltrim(str_replace($rootPath, '', $node->getPath()), '/');
		$children = [];
		try {
			$reason = $event->getExclusionReason($node);
			if ($reason !== null) {
				if ($this->reportMissingFiles) {
					$this->missingInfo[$filename] = $reason;
				}
				return;
			}

			$streamError = $this->streamNode($streamer, $node, $rootPath);
			if ($streamError !== null) {
				$this->logger->warning('Could not add file to the archive: ' . $streamError, ['path' => $node->getPath()]);
				if ($this->reportMissingFiles) {
					$this->missingInfo[$filename] = $streamError;
				}
			}

			if ($node instanceof NcFolder) {
				$children = $node->getDirectoryListing();
			}
		} catch (\Exception $e) {
			if (!$this->reportMissingFiles) {
				throw $e;
			}

			$this->logger->error("Error while streaming file '$filename'", ['exception' => $e]);
			$this->missingInfo[$filename] = $this->l10n->t('File could not be added to the archive. Please check the server logs for more information.');
			return;
		}

		foreach ($children as $child) {
			$this->streamTree($streamer, $event, $child, $rootPath);
		}
	}

	/**
	 * Tell sabre/dav not to trigger its own response sending logic as the handleDownload will have already sent the response
	 */
	public function afterDownload(Request $request, Response $response): ?false {
		if ($request->getHeader('X-Sabre-Original-Method') === 'HEAD') {
			return null;
		}

		if (!$this->streamed) {
			return null;
		}

		return false;
	}
}

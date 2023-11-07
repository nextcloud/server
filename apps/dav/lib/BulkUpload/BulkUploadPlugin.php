<?php
/**
 * @copyright Copyright (c) 2021, Louis Chemineau <louis@chmn.me>
 *
 * @author Louis Chemineau <louis@chmn.me>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\BulkUpload;

use OCA\DAV\Connector\Sabre\MtimeSanitizer;
use OCP\AppFramework\Http;
use OCP\Files\DavUtil;
use OCP\Files\Folder;
use Exception;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class BulkUploadPlugin extends ServerPlugin {
	private Folder $userFolder;
	private LoggerInterface $logger;

	/**
	 * Reference to main server object
	 *
	 * @var Server
	 */
	private $server;

	public function __construct(
		Folder $userFolder,
		LoggerInterface $logger
	) {
		$this->userFolder = $userFolder;
		$this->logger = $logger;
	}

	/**
	 * Register listener on POST requests with the httpPost method.
	 */
	public function initialize(Server $server): void {
		$this->server = $server;
		$server->on('method:POST', [$this, 'httpPost'], 10);
	}

	/**
	 * Handle POST requests on /dav/bulk
	 * - parsing is done with a MultipartContentsParser object
	 * - writing is done with the userFolder service
	 *
	 * Will respond with an object containing an ETag for every written files.
	 */
	public function httpPost(RequestInterface $request, ResponseInterface $response): bool {
		// Limit bulk upload to the /dav/bulk endpoint
		if ($request->getPath() !== "bulk") {
			return true;
		}

		$multiPartParser = new MultipartRequestParser($request, $this->logger);
		$writtenFiles = [];

		while (!$multiPartParser->isAtLastBoundary()) {
			try {
				[$headers, $content] = $multiPartParser->parseNextPart();
			} catch (\Exception $e) {
				// Return early if an error occurs during parsing.
				$this->logger->error($e->getMessage());
				$response->setStatus(Http::STATUS_BAD_REQUEST);
				$response->setBody(json_encode($writtenFiles, JSON_THROW_ON_ERROR));
				return false;
			}

			try {
				// TODO: Remove 'x-file-mtime' when the desktop client no longer use it.
				if (isset($headers['x-file-mtime'])) {
					$mtime = MtimeSanitizer::sanitizeMtime($headers['x-file-mtime']);
				} elseif (isset($headers['x-oc-mtime'])) {
					$mtime = MtimeSanitizer::sanitizeMtime($headers['x-oc-mtime']);
				} else {
					$mtime = null;
				}

				if (isset($headers['oc-file-type']) && $headers['oc-file-type'] == 1) {
					// TODO: store default value in global location
					$allowSymlinks = \OC::$server->get(\OC\AllConfig::class)->getSystemValueBool(
						'localstorage.allowsymlinks', false);
					if (!$allowSymlinks) {
						throw new Forbidden("Server does not allow the creation of symlinks!");
					}
					$symlinkPath = $headers['x-file-path'];
					$parentNode = $this->server->tree->getNodeForPath(dirname($symlinkPath));
					if(!$parentNode instanceof \OCA\DAV\Connector\Sabre\Directory) {
						throw new Exception("Unable to upload '$symlinkPath' because the remote directory does not support symlink creation!");
					}
					$etag = $parentNode->createSymlink(basename($symlinkPath), $content);
					$writtenFiles[$headers['x-file-path']] = [
						"error" => false,
						"etag" => $etag,
					];
					continue;
				}

				$node = $this->userFolder->newFile($headers['x-file-path'], $content);
				$node->touch($mtime);
				$node = $this->userFolder->getById($node->getId())[0];

				$writtenFiles[$headers['x-file-path']] = [
					"error" => false,
					"etag" => $node->getETag(),
					"fileid" => DavUtil::getDavFileId($node->getId()),
					"permissions" => DavUtil::getDavPermissions($node),
				];
			} catch (\Exception $e) {
				$this->logger->error($e->getMessage(), ['path' => $headers['x-file-path']]);
				$writtenFiles[$headers['x-file-path']] = [
					"error" => true,
					"message" => $e->getMessage(),
				];
			}
		}

		$response->setStatus(Http::STATUS_OK);
		$response->setBody(json_encode($writtenFiles, JSON_THROW_ON_ERROR));

		return false;
	}
}

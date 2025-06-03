<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Files\Sharing;

use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\Share\IShare;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Make sure that the destination is writable
 */
class FilesDropPlugin extends ServerPlugin {

	private ?IShare $share = null;
	private bool $enabled = false;

	public function setShare(IShare $share): void {
		$this->share = $share;
	}

	public function enable(): void {
		$this->enabled = true;
	}

	/**
	 * This initializes the plugin.
	 * It is ONLY initialized by the server on a file drop request.
	 */
	public function initialize(\Sabre\DAV\Server $server): void {
		$server->on('beforeMethod:*', [$this, 'beforeMethod'], 999);
		$server->on('method:MKCOL', [$this, 'onMkcol']);
		$this->enabled = false;
	}

	public function onMkcol(RequestInterface $request, ResponseInterface $response) {
		if (!$this->enabled || $this->share === null) {
			return;
		}

		$node = $this->share->getNode();
		if (!($node instanceof Folder)) {
			return;
		}

		// If this is a folder creation request we need
		// to fake a success so we can pretend every
		// folder now exists.
		$response->setStatus(201);
		return false;
	}

	public function beforeMethod(RequestInterface $request, ResponseInterface $response) {
		if (!$this->enabled || $this->share === null) {
			return;
		}

		$node = $this->share->getNode();
		if (!($node instanceof Folder)) {
			return;
		}

		// Retrieve the nickname from the request
		$nickname = $request->hasHeader('X-NC-Nickname')
			? trim(urldecode($request->getHeader('X-NC-Nickname')))
			: null;

		if ($request->getMethod() !== 'PUT') {
			// If uploading subfolders we need to ensure they get created
			// within the nickname folder
			if ($request->getMethod() === 'MKCOL') {
				if (!$nickname) {
					throw new BadRequest('A nickname header is required when uploading subfolders');
				}
			} else {
				throw new MethodNotAllowed('Only PUT is allowed on files drop');
			}
		}

		// If this is a folder creation request
		// let's stop there and let the onMkcol handle it
		if ($request->getMethod() === 'MKCOL') {
			return;
		}

		// Now if we create a file, we need to create the
		// full path along the way. We'll only handle conflict
		// resolution on file conflicts, but not on folders.

		// e.g files/dCP8yn3N86EK9sL/Folder/image.jpg
		$path = $request->getPath();
		$token = $this->share->getToken();

		// e.g files/dCP8yn3N86EK9sL
		$rootPath = substr($path, 0, strpos($path, $token) + strlen($token));
		// e.g /Folder/image.jpg
		$relativePath = substr($path, strlen($rootPath));
		$isRootUpload = substr_count($relativePath, '/') === 1;

		// Extract the attributes for the file request
		$isFileRequest = false;
		$attributes = $this->share->getAttributes();
		if ($attributes !== null) {
			$isFileRequest = $attributes->getAttribute('fileRequest', 'enabled') === true;
		}

		// We need a valid nickname for file requests
		if ($isFileRequest && !$nickname) {
			throw new BadRequest('A nickname header is required for file requests');
		}

		// We're only allowing the upload of
		// long path with subfolders if a nickname is set.
		// This prevents confusion when uploading files and help
		// classify them by uploaders.
		if (!$nickname && !$isRootUpload) {
			throw new BadRequest('A nickname header is required when uploading subfolders');
		}

		if ($nickname) {
			try {
				$node->verifyPath($nickname);
			} catch (\Exception $e) {
				// If the path is not valid, we throw an exception
				throw new BadRequest('Invalid nickname: ' . $nickname);
			}

			// Forbid nicknames starting with a dot
			if (str_starts_with($nickname, '.')) {
				throw new BadRequest('Invalid nickname: ' . $nickname);
			}

			// If we have a nickname, let's put
			// all files in the subfolder
			$relativePath = '/' . $nickname . '/' . $relativePath;
			$relativePath = str_replace('//', '/', $relativePath);
		}

		// Create the folders along the way
		$folder = $node;
		$pathSegments = $this->getPathSegments(dirname($relativePath));
		foreach ($pathSegments as $pathSegment) {
			if ($pathSegment === '') {
				continue;
			}

			try {
				// get the current folder
				$currentFolder = $folder->get($pathSegment);
				// check target is a folder
				if ($currentFolder instanceof Folder) {
					$folder = $currentFolder;
				} else {
					// otherwise look in the parent folder if we already create an unique folder name
					foreach ($folder->getDirectoryListing() as $child) {
						// we look for folders which match "NAME (SUFFIX)"
						if ($child instanceof Folder && str_starts_with($child->getName(), $pathSegment)) {
							$suffix = substr($child->getName(), strlen($pathSegment));
							if (preg_match('/^ \(\d+\)$/', $suffix)) {
								// we found the unique folder name and can use it
								$folder = $child;
								break;
							}
						}
					}
					// no folder found so we need to create a new unique folder name
					if (!isset($child) || $child !== $folder) {
						$folder = $folder->newFolder($folder->getNonExistingName($pathSegment));
					}
				}
			} catch (NotFoundException) {
				// the folder does simply not exist so we create it
				$folder = $folder->newFolder($pathSegment);
			}
		}

		// Finally handle conflicts on the end files
		$uniqueName = $folder->getNonExistingName(basename($relativePath));
		$relativePath = substr($folder->getPath(), strlen($node->getPath()));
		$path = '/files/' . $token . '/' . $relativePath . '/' . $uniqueName;
		$url = rtrim($request->getBaseUrl(), '/') . str_replace('//', '/', $path);
		$request->setUrl($url);
	}

	private function getPathSegments(string $path): array {
		// Normalize slashes and remove trailing slash
		$path = trim(str_replace('\\', '/', $path), '/');

		return explode('/', $path);
	}
}

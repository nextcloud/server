<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Files\Sharing;

use OCP\Files\Folder;
use OCP\Share\IShare;
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

		//
		if ($request->getMethod() !== 'PUT') {
			// If uploading subfolders we need to ensure they get created
			// within the nickname folder
			if ($request->getMethod() === 'MKCOL') {
				if (!$nickname) {
					throw new MethodNotAllowed('A nickname header is required when uploading subfolders');
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
			throw new MethodNotAllowed('A nickname header is required for file requests');
		}

		// We're only allowing the upload of
		// long path with subfolders if a nickname is set.
		// This prevents confusion when uploading files and help
		// classify them by uploaders.
		if (!$nickname && !$isRootUpload) {
			throw new MethodNotAllowed('A nickname header is required when uploading subfolders');
		}

		// If we have a nickname, let's put everything inside
		if ($nickname) {
			// Put all files in the subfolder
			$relativePath = '/' . $nickname . '/' . $relativePath;
			$relativePath = str_replace('//', '/', $relativePath);
		}

		// Create the folders along the way
		$folders = $this->getPathSegments(dirname($relativePath));
		foreach ($folders as $folder) {
			if ($folder === '') {
				continue;
			} // skip empty parts
			if (!$node->nodeExists($folder)) {
				$node->newFolder($folder);
			}
		}

		// Finally handle conflicts on the end files
		/** @var Folder */
		$folder = $node->get(dirname($relativePath));
		$uniqueName = $folder->getNonExistingName(basename(($relativePath)));
		$path = '/files/' . $token . '/' . dirname($relativePath) . '/' . $uniqueName;
		$url = $request->getBaseUrl() . str_replace('//', '/', $path);
		$request->setUrl($url);
	}

	private function getPathSegments(string $path): array {
		// Normalize slashes and remove trailing slash
		$path = rtrim(str_replace('\\', '/', $path), '/');

		// Handle absolute paths starting with /
		$isAbsolute = str_starts_with($path, '/');

		$segments = explode('/', $path);

		// Add back the leading slash for the first segment if needed
		$result = [];
		$current = $isAbsolute ? '/' : '';

		foreach ($segments as $segment) {
			if ($segment === '') {
				// skip empty parts
				continue;
			}
			$current = rtrim($current, '/') . '/' . $segment;
			$result[] = $current;
		}

		return $result;
	}
}

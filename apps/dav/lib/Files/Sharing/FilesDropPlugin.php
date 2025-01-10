<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Files\Sharing;

use OC\Files\View;
use OCP\Share\IShare;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Make sure that the destination is writable
 */
class FilesDropPlugin extends ServerPlugin {

	private ?View $view = null;
	private ?IShare $share = null;
	private bool $enabled = false;

	public function setView(View $view): void {
		$this->view = $view;
	}

	public function setShare(IShare $share): void {
		$this->share = $share;
	}

	public function enable(): void {
		$this->enabled = true;
	}


	/**
	 * This initializes the plugin.
	 *
	 * @param \Sabre\DAV\Server $server Sabre server
	 *
	 * @return void
	 * @throws MethodNotAllowed
	 */
	public function initialize(\Sabre\DAV\Server $server): void {
		$server->on('beforeMethod:*', [$this, 'beforeMethod'], 999);
		$this->enabled = false;
	}

	public function beforeMethod(RequestInterface $request, ResponseInterface $response): void {
		if (!$this->enabled || $this->share === null || $this->view === null) {
			return;
		}

		// Only allow file drop
		if ($request->getMethod() !== 'PUT') {
			throw new MethodNotAllowed('Only PUT is allowed on files drop');
		}

		// Always upload at the root level
		$path = explode('/', $request->getPath());
		$path = array_pop($path);

		// Extract the attributes for the file request
		$isFileRequest = false;
		$attributes = $this->share->getAttributes();
		$nickName = $request->hasHeader('X-NC-Nickname') ? urldecode($request->getHeader('X-NC-Nickname')) : null;
		if ($attributes !== null) {
			$isFileRequest = $attributes->getAttribute('fileRequest', 'enabled') === true;
		}

		// We need a valid nickname for file requests
		if ($isFileRequest && ($nickName == null || trim($nickName) === '')) {
			throw new MethodNotAllowed('Nickname is required for file requests');
		}
		
		// If this is a file request we need to create a folder for the user
		if ($isFileRequest) {
			// Check if the folder already exists
			if (!($this->view->file_exists($nickName) === true)) {
				$this->view->mkdir($nickName);
			}
			// Put all files in the subfolder
			$path = $nickName . '/' . $path;
		}
		
		$newName = \OC_Helper::buildNotExistingFileNameForView('/', $path, $this->view);
		$url = $request->getBaseUrl() . $newName;
		$request->setUrl($url);
	}

}

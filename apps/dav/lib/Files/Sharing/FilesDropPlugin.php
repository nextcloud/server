<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Files\Sharing;

use OC\Files\View;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Make sure that the destination is writable
 */
class FilesDropPlugin extends ServerPlugin {

	/** @var View */
	private $view;

	/** @var bool */
	private $enabled = false;

	/**
	 * @param View $view
	 */
	public function setView($view) {
		$this->view = $view;
	}

	public function enable() {
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
	public function initialize(\Sabre\DAV\Server $server) {
		$server->on('beforeMethod:*', [$this, 'beforeMethod'], 999);
		$this->enabled = false;
	}

	public function beforeMethod(RequestInterface $request, ResponseInterface $response) {
		if (!$this->enabled) {
			return;
		}

		if ($request->getMethod() !== 'PUT') {
			throw new MethodNotAllowed('Only PUT is allowed on files drop');
		}

		$path = explode('/', $request->getPath());
		$path = array_pop($path);

		$newName = \OC_Helper::buildNotExistingFileNameForView('/', $path, $this->view);
		$url = $request->getBaseUrl() . $newName;
		$request->setUrl($url);
	}
}

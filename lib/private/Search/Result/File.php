<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Search\Result;

use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\IPreview;
use OCP\IUserSession;

/**
 * A found file
 * @deprecated 20.0.0
 */
class File extends \OCP\Search\Result {
	/**
	 * Type name; translated in templates
	 * @var string
	 * @deprecated 20.0.0
	 */
	public $type = 'file';

	/**
	 * Path to file
	 * @var string
	 * @deprecated 20.0.0
	 */
	public $path;

	/**
	 * Size, in bytes
	 * @var int
	 * @deprecated 20.0.0
	 */
	public $size;

	/**
	 * Date modified, in human readable form
	 * @var string
	 * @deprecated 20.0.0
	 */
	public $modified;

	/**
	 * File mime type
	 * @var string
	 * @deprecated 20.0.0
	 */
	public $mime_type;

	/**
	 * File permissions:
	 *
	 * @var string
	 * @deprecated 20.0.0
	 */
	public $permissions;

	/**
	 * Has a preview
	 *
	 * @var string
	 * @deprecated 20.0.0
	 */
	public $has_preview;

	/**
	 * Create a new file search result
	 * @param FileInfo $data file data given by provider
	 * @deprecated 20.0.0
	 */
	public function __construct(FileInfo $data) {
		$path = $this->getRelativePath($data->getPath());

		$this->id = $data->getId();
		$this->name = $data->getName();
		$this->link = \OC::$server->getURLGenerator()->linkToRoute(
			'files.view.index',
			[
				'dir' => dirname($path),
				'scrollto' => $data->getName(),
			]
		);
		$this->permissions = $data->getPermissions();
		$this->path = $path;
		$this->size = $data->getSize();
		$this->modified = $data->getMtime();
		$this->mime_type = $data->getMimetype();
		$this->has_preview = $this->hasPreview($data);
	}

	/**
	 * @var Folder $userFolderCache
	 * @deprecated 20.0.0
	 */
	protected static $userFolderCache = null;

	/**
	 * converts a path relative to the users files folder
	 * eg /user/files/foo.txt -> /foo.txt
	 * @param string $path
	 * @return string relative path
	 * @deprecated 20.0.0
	 */
	protected function getRelativePath($path) {
		if (!isset(self::$userFolderCache)) {
			$userSession = \OC::$server->get(IUserSession::class);
			$userID = $userSession->getUser()->getUID();
			self::$userFolderCache = \OC::$server->getUserFolder($userID);
		}
		$relativePath = self::$userFolderCache->getRelativePath($path);
		if ($relativePath === null) {
			throw new \Exception("Search result not in user folder");
		}
		return $relativePath;
	}

	/**
	 * Is the preview available
	 * @param FileInfo $data
	 * @return bool
	 * @deprecated 20.0.0
	 */
	protected function hasPreview($data) {
		$previewManager = \OC::$server->get(IPreview::class);
		return $previewManager->isAvailable($data);
	}
}

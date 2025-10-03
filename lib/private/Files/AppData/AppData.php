<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\AppData;

use OC\Files\SimpleFS\SimpleFolder;
use OC\SystemConfig;
use OCP\Cache\CappedMemoryCache;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFolder;

/**
 * Concrete implementation of {@see \OCP\Files\IAppData}.
 *
 * Wraps IRootFolder and ISimpleFolder objects to provide app-specific data folder access to Nextcloud's virtual file system.
 *
 * @internal This class is not part of the public API and may change without notice.
 */
class AppData implements IAppData {
	/**
	 * Cached reference to the app-specific Folder for this application.
	 * This is the Folder instance for "{$instanceAppDataFolderName}/{$appId}" and is created
	 * or retrieved on first access via getOrCreateAppDataFolder().
	 *
	 * @var Folder|null
	 */
	private ?Folder $appDataFolder = null;
	/**
	 * Caches references to subfolders or lookup errors for app-specific data directories.
	 * Keys are in the format "{$appId}/{$name}". Values are either ISimpleFolder on success,
	 * or NotFoundException if the folder was not found.
	 *
	 * @var CappedMemoryCache<ISimpleFolder|NotFoundException>
	 */
	private CappedMemoryCache $folders;

	public function __construct(
		private IRootFolder $rootFolder,
		private SystemConfig $config,
		private string $appId
	) {
		$this->folders = new CappedMemoryCache();
	}

	/**
	 * {@inheritdoc}
	 *
	 * TODO: Possible memory optimization opportunity for larger folders.
	 */
	public function getDirectoryListing(): array {
		$nodes = $this->getOrCreateAppDataFolder()->getDirectoryListing();
		$subFolders = [];

		foreach ($nodes as $node) {
			if ($node instanceof Folder) {
				$subFolders[] = new SimpleFolder($node);
			}
		}
		
		/** @return ISimpleFolder[] */
		return $subFolders;
	}

	/**
	 * {@inheritdoc}
	 *
	 * Uses an in-memory cache for performance.
	 */
	public function getFolder(string $name): ISimpleFolder {
		$cacheKey = $this->appId . '/' . $name;

		// Get the app-specific data folder for this app
		$appDataFolder = $this->getOrCreateAppDataFolder();

		// Check cache
		$cachedFolder = $this->folders->get($cacheKey);			
		if ($cachedFolder instanceOf ISimpleFolder) {
			return $cachedFolder;
		}
		if ($cachedFolder instanceof \Exception) {
			throw $cachedFolder;
		}

		// Handle special case: app's appdata root folder or empty name
		if ($name === '/' || $name === '') {
			$folder = $appDataFolder;
		} else { // Handle standard case: sub-folder in app's appdata folder
			// Retrieve or create the subfolder
			try {
				$appDataPath = $this->getInstanceAppDataFolderName() . '/' . $this->appId . '/' . $name;
				$folder = $this->rootFolder->get($appDataPath);
				// $folder = $appDataFolder->getFolder($name);
			} catch (NotFoundException $e) {
				try {
					$folder = $appDataFolder->newFolder($name);
				} catch (NotPermittedException $e) {
					throw new \RuntimeException(
						"Could not create folder '$name' for app '{$this->appId}': " . $e->getMessage(),
						0,
						$e
					);
				}
			}
		}

		// Wrap, cache, return
		$simpleFolder = new SimpleFolder($folder);
		$this->folders->set($cacheKey, $simpleFolder);
		return $simpleFolder;
	}

	/**
	 * {@inheritdoc}
	 */
	public function newFolder(string $name): ISimpleFolder {
		$cacheKey = $this->appId . '/' . $name;
		$newFolder = $this->getOrCreateAppDataFolder()->newFolder($name);

		// Wrap, cache, return
		$subFolder = new SimpleFolder($newFolder);
		$this->folders->set($cacheKey, $subFolder);
		return $subFolder;
	}

	/**
	 * Returns the name of the top-level appdata folder for the current Nextcloud instance.
	 *
	 * @return string The appdata folder name, including the instance identifier.
	 */
	private function getInstanceAppDataFolderName(): string {
		$instanceId = $this->config->getValue('instanceid', null);
		if ($instanceId === null) {
			throw new \RuntimeException('no instance id!');
		}

		$instanceAppDataFolderName = 'appdata_' . $instanceId;
		return $instanceAppDataFolderName;
	}

	/**
	 * Retrieves the top-level appdata folder for the current Nextcloud instance.
	 * Creates the folder if it does not exist.
	 *
	 * @return Folder The instance appdata folder.
	 * @throws \RuntimeException If the folder cannot be accessed or created due to permissions.
	 *
	 * XXX: Not sure this really needs to be protected. [Actually, it's used by OC\Preview\Storage\Root; rename there too if doing so
	 */
	protected function getInstanceAppDataFolder(): Folder {
		$instanceAppDataFolderName = $this->getInstanceAppDataFolderName();

		try {
			/** @var Folder $node */
			$node = $this->rootFolder->get($instanceAppDataFolderName);
			return $node;
		} catch (NotFoundException $e) {
			try {
				return $this->rootFolder->newFolder($instanceAppDataFolderName);
			} catch (NotPermittedException $e) {
				throw new \RuntimeException('Could not get nor create appdata folder for ' . $this->appId);
			}
		}
	}

	/**
	 * Retrieves the app-specific data folder for the current application.
	 * Creates the folder if it does not already exist and caches the reference.
	 *
	 * @return Folder The Folder object representing the app's data directory.
	 * @throws \RuntimeException If the folder cannot be accessed or created.
	 */
	private function getOrCreateAppDataFolder(): Folder {
		// Cached
		if ($this->appDataFolder !== null) {
			return $this->appDataFolder;
		}
		
		$instanceAppDataFolderName = $this->getInstanceAppDataFolderName();
		$appDataFolderName = $instanceAppDataFolderName . '/' . $this->appId;

		// Try direct lookup
		try {
			$this->appDataFolder = $this->rootFolder->get($appDataFolderName);
		} catch (NotFoundException $e) {
			// XXX: This seems redundant...
			// Not found, try instance + appId
			$instanceAppDataFolder = $this->getInstanceAppDataFolder();
			try {
				$this->appDataFolder = $instanceAppDataFolder->get($this->appId);
			} catch (NotFoundException $e) {
				// Still not found, try to create
				try {
					$this->appDataFolder = $instanceAppDataFolder->newFolder($this->appId);
				} catch (NotPermittedException $e) {
					throw new \RuntimeException('Could not get nor create appdata folder for ' . $this->appId);
				}
			}
		}
		return $this->appDataFolder;
	}

	/**
	 * Returns the numeric ID of the app-specific data folder.
	 *
	 * @return int The folder's unique identifier.
	 *
	 * XXX This is not used here nor defined in the interface; should be removed... or added to the interface?
	 */
	public function getId(): int {
		return $this->getOrCreateAppDataFolder()->getId();
	}
}

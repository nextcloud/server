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
 * Implements app-specific data storage {@see \OCP\Files\IAppData} by wrapping IRootFolder and 
 * Folder objects and re-using {@see \OCP\Files\SimpleFS\SIimpleFolder} overlaid on Nextcloud's 
 * virtual file system.
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
	 * Wraps retrieved Folder in an SimpleFolder.
	 * Uses an in-memory cache for performance.
	 *
	 * @throws \RuntimeException for unrecoverable errors
	 */
	public function getFolder(string $name): ISimpleFolder {
		$cacheKey = $this->buildCacheKey($name);
		// Check cache
		$cachedFolder = $this->folders->get($cacheKey);			
		if ($cachedFolder instanceof ISimpleFolder) {
			return $cachedFolder;
		}
		if ($cachedFolder instanceof NotFoundException) {
			// We can use the cached NotFound w/o cache management since cache is in-request only
			throw $cachedFolder;
		}

		if ($name === '/') {
			// Special case: app's appdata root folder
			// Get the Folder object representing this app's appdata root folder
			$appDataFolder = $this->getOrCreateAppDataFolder();
			$requestedFolder = $appDataFolder;
		// Try to get the subfolder (within app's appdata folder) by path
		} else {
			// Standard case: subfolder within app's appdata
			try {
				// We don't want to create the subfolder if it doesn't exist.
				// So we retrieve the subfolder directly by path,
				// instead of calling getOrCreateAppDataFolder().
				$appDataPath = $this->buildAppDataPath($name);
				$requestedFolder = $this->rootFolder->get($appDataPath);
			} catch (NotFoundException $e) {
				$this->folders->set($cacheKey, $e);
				throw $e;
			}
		}

		return $this->wrapAndCacheFolder($cacheKey, $requestedFolder);
	}

	/**
	 * {@inheritdoc}
	 */
	public function newFolder(string $name): ISimpleFolder {
		$cacheKey = $this->buildCacheKey($name);
		$newFolder = $this->getOrCreateAppDataFolder()->newFolder($name);

		return $this->wrapAndCacheFolder($cacheKey, $newFolder);
	}

	/**
	 * Returns the name of the top-level appdata folder for the current Nextcloud instance.
	 *
	 * @return string The appdata folder name, including the instance identifier.
	 * @throws \RuntimeException for unrecoverable errors
	 */
	private function getInstanceAppDataFolderName(): string {
		$instanceId = $this->config->getValue('instanceid', null);
		if ($instanceId === null) {
			throw new \RuntimeException(
				'Could not determine instance appdata folder; configuration is missing an instance id!'
			);
		}

		$instanceAppDataFolderName = 'appdata_' . $instanceId;
		return $instanceAppDataFolderName;
	}

	/**
	 * Retrieves the top-level appdata folder for the current Nextcloud instance.
	 * Creates the folder if it does not exist.
	 *
	 * Protected rather than private since it's also used by downstream \OC\Preview\Storage\Root class.
	 *
	 * @return Folder The instance appdata folder.
	 * @throws \RuntimeException If the folder cannot be created due to permissions.
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
				throw new \RuntimeException(
					'Could not get nor create instance appdata folder '
					. $instanceAppDataFolderName
					. ' while trying to get or create dedicated appdata folder for '
					. $this->appId
					. '. Check data directory permissions!'
				);
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
		if ($this->appDataFolder !== null) {
			return $this->appDataFolder;
		}

		// Try direct lookup
		$appDataFolderName = $this->buildAppDataPath();
		try {
			$this->appDataFolder = $this->rootFolder->get($appDataFolderName);
			return $this->appDataFolder;
		} catch (NotFoundException $e) {
			// Continue
		}

		// Try indirect lookup (instance + appId) - slower/more queries
		// TODO: This fallback seems redundant/unnecessary at this point...
		// - Can be removed since #12883?
		// - I suspect it was just left in to be conservative, but is presumably already a no-op.
		$instanceAppDataFolder = $this->getInstanceAppDataFolder();
		try {
			$this->appDataFolder = $instanceAppDataFolder->get($this->appId);
			return $this->appDataFolder;
		} catch (NotFoundException $e) {
			// Continue
		}

		// Still not found, try to create
		try {
			$this->appDataFolder = $instanceAppDataFolder->newFolder($this->appId);
			return $this->appDataFolder;
		} catch (NotPermittedException $e) {
			throw new \RuntimeException('Could not get nor create appdata folder for ' . $this->appId);
		}
	}

	/**
	 * Returns the numeric ID of the app-specific data folder.
	 *
	 * Public rather than private since it's called by OC\Preview\BackgroundCleanupJob class.
	 *
	 * Note: Seems to only be used by downstream \OC\Preview\Storage\Root class.
	 *
	 * @return int The folder's unique identifier.
	 */
	public function getId(): int {
		return $this->getOrCreateAppDataFolder()->getId();
	}

	/**
	 * Validates the folder name and generates a cache key.
	 *
	 * @param string $name
	 * @return string
	 * @throws \RuntimeException if the name is empty
	 */
	private function buildCacheKey(string $name): string {
		if ($name === '') {
			throw new \RuntimeException('Appdata folder name cannot be (empty).');
		}
		return $this->appId . '/' . $name;
	}

	/**
	 * Wraps a Folder as SimpleFolder, caches it, and returns it.
	 *
	 * @param string $cacheKey
	 * @param Folder $folder
	 * @return ISimpleFolder
	 */
	private function wrapAndCacheFolder(string $cacheKey, Folder $folder): ISimpleFolder {
		$simpleFolder = new SimpleFolder($folder);
		$this->folders->set($cacheKey, $simpleFolder);
		return $simpleFolder;
	}
	
	/**
	 * Builds the full path to a subfolder in an app's appdata directory.
	 *
	 * If $subfolder is provided, returns the path to that subfolder;
	 * otherwise returns the path to the root of the app's appdata folder.
	 *
	 * @param string|null $subfolder Optional subfolder name
	 * @return string
	 */
	private function buildAppDataPath(?string $subfolder = null): string {
		$base = $this->getInstanceAppDataFolderName() . '/' . $this->appId;
		return $subfolder === null ? $base : $base . '/' . $subfolder;
	}
}

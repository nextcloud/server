<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-FileCopyrightText: 2014 Vincent Petry <pvince81@owncloud.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\ITagManager;
use OCP\ITags;
use OCP\IUserSession;
use OCA\DAV\Connector\Sabre\Directory;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Tree;

/**
 * Sabre DAV plugin that exposes user-level/private file tags via WebDAV.
 *
 * Handles reading, updating, and caching user-specific tags (including favorites)
 * for files and folders via DAV property events. 
 * 
 * Only user/private tags and favorites are covered by this plugin -- system-level
 * (public/global) tags are managed by the OCA\DAV\SystemTag\SystemTagPlugin class.
 *
 * This plugin enables clients to manage personal file tags as WebDAV properties.
 */
class TagsPlugin extends ServerPlugin {

	public const NS_OWNCLOUD = 'http://owncloud.org/ns';
	public const TAGS_PROPERTYNAME = '{http://owncloud.org/ns}tags';
	public const FAVORITE_PROPERTYNAME = '{http://owncloud.org/ns}favorite';
	public const TAG_FAVORITE = '_$!<Favorite>!$_';

	private Server $server;
	/** @var ITags|null Lazily-initialized tag manager for file user tags. */
	private ?ITags $tagger;
	/** @var array<int, string[]> Maps file IDs to arrays of tag names */
	private array $cachedTagsByFileId;
	/** @var array<string, bool> Maps directory paths to prefetch status. */
	private array $prefetchedDirectories;

	public function __construct(
		private readonly Tree $tree,
		private readonly ITagManager $tagManager,
		private readonly IUserSession $userSession,
	) {
		$this->tagger = null;
		$this->cachedTagsByFileId = [];
		$this->prefetchedDirectories = [];
	}

	/**
	 * Initializes the plugin by registering event subscriptions with the SabreDAV server.
	 */
	public function initialize(Server $server): void {
		$this->server = $server;

		// Register our custom namespace
		$this->server->xml->namespaceMap[self::NS_OWNCLOUD] = 'oc';
		// Map the custom tags property to the TagList class for (de)serialization
		$this->server->xml->elementMap[self::TAGS_PROPERTYNAME] = TagList::class;

		$this->server->on('propFind', $this->handleGetProperties(...));
		$this->server->on('propPatch', $this->handleUpdateProperties(...));

		// Register handler to preload tags for collections (folders) to optimize tag lookups.
		// - Emitted by OCA\DAV\Connector\Sabre\PropFindPreloadNotifyPlugin
		$this->server->on('preloadCollection', $this->handlePreloadCollection(...));
		// Register handler to preload tags for a batch of nodes requested by the search backend.
		// - Emitted by OCA\DAV\Files\FileSearchBackend
		$this->server->on('preloadProperties', $this->handlePreloadProperties(...));
	}

	/**
	 * Adds tags and favorite properties to the PROPFIND response for a given node if requested.
	 */
	private function handleGetProperties(PropFind $propFind, INode $node): void {
		if (!($node instanceof Node)) {
			return;
		}

		// Cache tag meta data for this node in this handler call
		$tagData = null;

		$propFind->handle(
			self::TAGS_PROPERTYNAME,
			function () use (&$tagData, $node) {
				if ($tagData === null) {
					// getTagData returns ['tags' => [...], 'isFav' => true/false]
					$tagData = $this->getTagData($node->getId());
				}
				return new TagList($tagData['tags']);
			}
		);
		$propFind->handle(
			self::FAVORITE_PROPERTYNAME,
			function () use (&$tagData, $node) {
				if ($tagData === null) {
					$tagData = $this->getTagData($node->getId());
				}
				return $tagData['isFav'] ? 1 : 0;
			}
		);
	}

	/**
	 * Handles PROPPATCH property updates for tags and favorite on a DAV node.
	 */
	private function handleUpdateProperties(string $path, PropPatch $propPatch): void {
		$node = $this->tree->getNodeForPath($path);

		if (!($node instanceof Node)) {
			return;
		}

		$fileId = $node->getId();

		// Handler for updating tags
		$propPatch->handle(
			self::TAGS_PROPERTYNAME,
			function ($tagList) use ($fileId, $path) {
				if ($tagList instanceof TagList) {
					$this->updateTags($fileId, $tagList->getTags(), $path);
				}
				return true; // TODO: Consider returning false/400 for bad input
			}
		);
		// Handling for updating favorite status
		$propPatch->handle(
			self::FAVORITE_PROPERTYNAME,
			function ($favState) use ($fileId, $path) {
				$isFavorite = ((int)$favState === 1 || $favState === true || $favState === 'true');
				if ($isFavorite) {
					$this->getTagger()->tagAs($fileId, self::TAG_FAVORITE, $path);
				} else {
					$this->getTagger()->unTag($fileId, self::TAG_FAVORITE, $path);
				}
				return is_null($favState) ? 204 : 200; // 204 = unfavorited; 200 = favorited
			}
		);
	}

	/**
	 * Preload tags for a directory and its immediate children if tags or favorite properties are requested.
	 */
	private function handlePreloadCollection(PropFind $propFind, ICollection $directory): void {
		if (!($directory instanceof Node && $directory instanceof Directory)) {
			return;
		}

		$path = $directory->getPath();

		// Nothing to do if already cached
		if (isset($this->prefetchedDirectories[$path])) {
			return;
		}
		
		$tagsRequested = !is_null($propFind->getStatus(self::TAGS_PROPERTYNAME));
		$favoriteRequested = !is_null($propFind->getStatus(self::FAVORITE_PROPERTYNAME));

		// Only preload if tags/favorite status are requested
		if (!$tagsRequested && !$favoriteRequested) {
			return;
		}
		
		$fileIds = [];
		$fileIds [] = (int)$directory->getId();
		// Note: Only depth <= 1 is supported
		foreach ($directory->getChildren() as $child) {
			if ($child instanceof Node) {
				$fileIds[] = (int)$child->getId();
			}
		}
		$this->prefetchTagsForFileIds($fileIds);
		$this->prefetchedDirectories[$path] = true;
	}

	/**
	 * Prefetch and cache tags/favorites for the given nodes, if requested.
	 *
	 * @param Node[] $nodes Files or directories for which to prefetch properties.
	 * @param string[] $requestedProperties List of requested property names.
	 */
	private function handlePreloadProperties(array $nodes, array $requestedProperties): void {
		$tagsRequested = in_array(self::TAGS_PROPERTYNAME, $requestedProperties, true);
		$favoriteRequested = in_array(self::FAVORITE_PROPERTYNAME, $requestedProperties, true);

		// Only preload if tags/favorite status are requested
		if (!$tagsRequested && !$favoriteRequested) {
			return;
		}

		$fileIds = [];
		foreach ($nodes as $node) {
			if ($node instanceof Node) {
				$fileIds[] = $node->getId();
			}
		}

		if (!empty($fileIds)) {
			$this->prefetchTagsForFileIds($fileIds);
		}
	}

	/**
	 * Returns and caches the tagger instance for file objects.
	 */
	private function getTagger(): ITags {
		if ($this->tagger === null) {
			$this->tagger = $this->tagManager->load('files');
		}
		return $this->tagger;
	}

	/**
	 * Fetches the tags and favorite status for a given node.
	 *
	 * @param int|null $fileId The file ID of the node (file/folder), or null if unavailable.
	 * @return array{tags: string[], isFav: bool}
	 */
	private function getTagData(?int $fileId): array {
		// Default to empty tag list and not favorite if fileId is null
		if ($fileId === null) {
			return [
				'tags' => [],
				'isFav' => false,
			];
		}

		$tags = $this->getTags($fileId);
		$isFav = false;

		if ($tags) {
			$favPos = array_search(self::TAG_FAVORITE, $tags);
			if ($favPos !== false) {
				$isFav = true;
				unset($tags[$favPos]);
				// Not re-indexed (harmless currently)
			}
		}

		return [
			'tags' => $tags,
			'isFav' => $isFav,
		];
	}

	/**
	 * Get tags for a given file/folder by its ID.
	 *
	 * @param int $fileId The file/folder id to look up.
	 * @return string[] Array of tag names (empty if none or not found).
	 */
	private function getTags(int $fileId): array {
		if (isset($this->cachedTagsByFileId[$fileId])) {
			return $this->cachedTagsByFileId[$fileId];
		} 

		$tagMap = $this->getTagger()->getTagsForObjects([$fileId]);
		if (!is_array($tagMap) || !isset($tagMap[$fileId]) || !is_array($tagMap[$fileId])) {
			return [];
		}

		return $tagMap[$fileId];
	}

	/**
	 * Bulk prefetch and cache tags for an array of file IDs.
	 *
	 * @param int[] $fileIds Array of file/folder IDs to prefetch tags for.
	 */
	private function prefetchTagsForFileIds(array $fileIds): void {
		if (empty($fileIds)) {
			return;
		}

		$tagMap = $this->getTagger()->getTagsForObjects($fileIds);
		if (!is_array($tagMap)) {
			$tagMap = [];
		}

		foreach ($fileIds as $fileId) {
			$this->cachedTagsByFileId[$fileId] = $tagMap[$fileId] ?? [];
		}
	}

	/**
	 * Updates the tags of the given file id, skipping the special favorite tag.
	 *
	 * @param int $fileId
	 * @param string[] $tags List of tags to set (favorite handled separately).
	 * @param string $path DAV path.
	 */
	private function updateTags(int $fileId, array $tags, string $path): void {
		$tagger = $this->getTagger();

		$currentTags = $this->getTags($fileId) ?? [];
		// Remove favorite tag from both sets up front
		$currentTags = $this->removeFavoriteTag($currentTags);
		$tags = $this->removeFavoriteTag($tags);

		$tagsToAdd = array_diff($tags, $currentTags);
		foreach ($tagsToAdd as $tag) {
			$tagger->tagAs($fileId, $tag, $path);
		}

		$tagsToRemove = array_diff($currentTags, $tags);
		foreach ($tagsToRemove as $tag) {
			$tagger->unTag($fileId, $tag, $path);
		}
	}

	/**
	 * Removes the special favorite tag from a tag array.
	 *
	 * Does not re-index.
	 */
	private function removeFavoriteTag(array $tags): array {
		return array_filter($tags, fn($tag) => $tag !== self::TAG_FAVORITE);
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\SystemTag;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Connector\Sabre\Node;
use OCP\AppFramework\Http;
use OCP\Constants;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagAlreadyExistsException;
use OCP\SystemTag\TagCreationForbiddenException;
use OCP\SystemTag\TagUpdateForbiddenException;
use OCP\Util;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\UnsupportedMediaType;
use Sabre\DAV\ICollection;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Sabre plugin to handle system tags:
 *
 * - makes it possible to create new tags with POST operation
 * - get/set Webdav properties for tags
 *
 */
class SystemTagPlugin extends \Sabre\DAV\ServerPlugin {

	// namespace
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';
	public const NS_NEXTCLOUD = 'http://nextcloud.org/ns';
	public const ID_PROPERTYNAME = '{http://owncloud.org/ns}id';
	public const DISPLAYNAME_PROPERTYNAME = '{http://owncloud.org/ns}display-name';
	public const USERVISIBLE_PROPERTYNAME = '{http://owncloud.org/ns}user-visible';
	public const USERASSIGNABLE_PROPERTYNAME = '{http://owncloud.org/ns}user-assignable';
	public const GROUPS_PROPERTYNAME = '{http://owncloud.org/ns}groups';
	public const CANASSIGN_PROPERTYNAME = '{http://owncloud.org/ns}can-assign';
	public const SYSTEM_TAGS_PROPERTYNAME = '{http://nextcloud.org/ns}system-tags';
	public const NUM_FILES_PROPERTYNAME = '{http://nextcloud.org/ns}files-assigned';
	public const REFERENCE_FILEID_PROPERTYNAME = '{http://nextcloud.org/ns}reference-fileid';
	public const OBJECTIDS_PROPERTYNAME = '{http://nextcloud.org/ns}object-ids';
	public const COLOR_PROPERTYNAME = '{http://nextcloud.org/ns}color';

	/**
	 * @var \Sabre\DAV\Server $server
	 */
	private $server;

	/** @var array<int, string[]> */
	private array $cachedTagMappings = [];
	/** @var array<string, ISystemTag> */
	private array $cachedTags = [];

	public function __construct(
		protected ISystemTagManager $tagManager,
		protected IGroupManager $groupManager,
		protected IUserSession $userSession,
		protected IRootFolder $rootFolder,
		protected ISystemTagObjectMapper $tagMapper,
	) {
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param \Sabre\DAV\Server $server
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$server->xml->namespaceMap[self::NS_OWNCLOUD] = 'oc';
		$server->xml->namespaceMap[self::NS_NEXTCLOUD] = 'nc';

		$server->xml->elementMap[self::OBJECTIDS_PROPERTYNAME] = SystemTagsObjectList::class;

		$server->protectedProperties[] = self::ID_PROPERTYNAME;

		$server->on('preloadCollection', $this->preloadCollection(...));
		$server->on('propFind', [$this, 'handleGetProperties']);
		$server->on('propPatch', [$this, 'handleUpdateProperties']);
		$server->on('method:POST', [$this, 'httpPost']);

		$this->server = $server;
	}

	/**
	 * POST operation on system tag collections
	 *
	 * @param RequestInterface $request request object
	 * @param ResponseInterface $response response object
	 * @return null|false
	 */
	public function httpPost(RequestInterface $request, ResponseInterface $response) {
		$path = $request->getPath();

		// Making sure the node exists
		$node = $this->server->tree->getNodeForPath($path);
		if ($node instanceof SystemTagsByIdCollection || $node instanceof SystemTagsObjectMappingCollection) {
			$data = $request->getBodyAsString();

			$tag = $this->createTag($data, $request->getHeader('Content-Type'));

			if ($node instanceof SystemTagsObjectMappingCollection) {
				// also add to collection
				$node->createFile($tag->getId());
				$url = $request->getBaseUrl() . 'systemtags/';
			} else {
				$url = $request->getUrl();
			}

			if ($url[strlen($url) - 1] !== '/') {
				$url .= '/';
			}

			$response->setHeader('Content-Location', $url . $tag->getId());

			// created
			$response->setStatus(Http::STATUS_CREATED);
			return false;
		}
	}

	/**
	 * Creates a new tag
	 *
	 * @param string $data JSON encoded string containing the properties of the tag to create
	 * @param string $contentType content type of the data
	 * @return ISystemTag newly created system tag
	 *
	 * @throws BadRequest if a field was missing
	 * @throws Conflict if a tag with the same properties already exists
	 * @throws UnsupportedMediaType if the content type is not supported
	 */
	private function createTag($data, $contentType = 'application/json') {
		if (explode(';', $contentType)[0] === 'application/json') {
			$data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
		} else {
			throw new UnsupportedMediaType();
		}

		if (!isset($data['name'])) {
			throw new BadRequest('Missing "name" attribute');
		}

		$tagName = $data['name'];
		$userVisible = true;
		$userAssignable = true;

		if (isset($data['userVisible'])) {
			$userVisible = (bool)$data['userVisible'];
		}

		if (isset($data['userAssignable'])) {
			$userAssignable = (bool)$data['userAssignable'];
		}

		$groups = [];
		if (isset($data['groups'])) {
			$groups = $data['groups'];
			if (is_string($groups)) {
				$groups = explode('|', $groups);
			}
		}

		if ($userVisible === false || $userAssignable === false || !empty($groups)) {
			if (!$this->userSession->isLoggedIn() || !$this->groupManager->isAdmin($this->userSession->getUser()->getUID())) {
				throw new BadRequest('Not sufficient permissions');
			}
		}

		try {
			$tag = $this->tagManager->createTag($tagName, $userVisible, $userAssignable);
			if (!empty($groups)) {
				$this->tagManager->setTagGroups($tag, $groups);
			}
			return $tag;
		} catch (TagAlreadyExistsException $e) {
			throw new Conflict('Tag already exists', 0, $e);
		} catch (TagCreationForbiddenException $e) {
			throw new Forbidden('You don’t have permissions to create tags', 0, $e);
		}
	}

	private function preloadCollection(
		PropFind $propFind,
		ICollection $collection,
	): void {
		if (!$collection instanceof Node) {
			return;
		}

		if ($collection instanceof Directory
			&& !isset($this->cachedTagMappings[$collection->getId()])
			&& $propFind->getStatus(
				self::SYSTEM_TAGS_PROPERTYNAME
			) !== null) {
			$fileIds = [$collection->getId()];

			// note: pre-fetching only supported for depth <= 1
			$folderContent = $collection->getChildren();
			foreach ($folderContent as $info) {
				if ($info instanceof Node) {
					$fileIds[] = $info->getId();
				}
			}

			$tags = $this->tagMapper->getTagIdsForObjects($fileIds, 'files');

			$this->cachedTagMappings += $tags;
			$emptyFileIds = array_diff($fileIds, array_keys($tags));

			// also cache the ones that were not found
			foreach ($emptyFileIds as $fileId) {
				$this->cachedTagMappings[$fileId] = [];
			}
		}
	}

	/**
	 * Retrieves system tag properties
	 *
	 * @param PropFind $propFind
	 * @param \Sabre\DAV\INode $node
	 *
	 * @return void
	 */
	public function handleGetProperties(
		PropFind $propFind,
		\Sabre\DAV\INode $node,
	) {
		if ($node instanceof Node) {
			$this->propfindForFile($propFind, $node);
			return;
		}

		if (!$node instanceof SystemTagNode && !$node instanceof SystemTagMappingNode && !$node instanceof SystemTagObjectType) {
			return;
		}

		// child nodes from systemtags-assigned should point to normal tag endpoint
		if (preg_match('/^systemtags-assigned\/[0-9]+/', $propFind->getPath())) {
			$propFind->setPath(str_replace('systemtags-assigned/', 'systemtags/', $propFind->getPath()));
		}

		$propFind->handle(FilesPlugin::GETETAG_PROPERTYNAME, function () use ($node): string {
			return '"' . ($node->getSystemTag()->getETag() ?? '') . '"';
		});

		$propFind->handle(self::ID_PROPERTYNAME, function () use ($node) {
			return $node->getSystemTag()->getId();
		});

		$propFind->handle(self::DISPLAYNAME_PROPERTYNAME, function () use ($node) {
			return $node->getSystemTag()->getName();
		});

		$propFind->handle(self::USERVISIBLE_PROPERTYNAME, function () use ($node) {
			return $node->getSystemTag()->isUserVisible() ? 'true' : 'false';
		});

		$propFind->handle(self::USERASSIGNABLE_PROPERTYNAME, function () use ($node) {
			// this is the tag's inherent property "is user assignable"
			return $node->getSystemTag()->isUserAssignable() ? 'true' : 'false';
		});

		$propFind->handle(self::CANASSIGN_PROPERTYNAME, function () use ($node) {
			// this is the effective permission for the current user
			return $this->tagManager->canUserAssignTag($node->getSystemTag(), $this->userSession->getUser()) ? 'true' : 'false';
		});

		$propFind->handle(self::COLOR_PROPERTYNAME, function () use ($node) {
			return $node->getSystemTag()->getColor() ?? '';
		});

		$propFind->handle(self::GROUPS_PROPERTYNAME, function () use ($node) {
			if (!$this->groupManager->isAdmin($this->userSession->getUser()->getUID())) {
				// property only available for admins
				throw new Forbidden();
			}
			$groups = [];
			// no need to retrieve groups for namespaces that don't qualify
			if ($node->getSystemTag()->isUserVisible() && !$node->getSystemTag()->isUserAssignable()) {
				$groups = $this->tagManager->getTagGroups($node->getSystemTag());
			}
			return implode('|', $groups);
		});

		if ($node instanceof SystemTagNode) {
			$propFind->handle(self::NUM_FILES_PROPERTYNAME, function () use ($node): int {
				return $node->getNumberOfFiles();
			});

			$propFind->handle(self::REFERENCE_FILEID_PROPERTYNAME, function () use ($node): int {
				return $node->getReferenceFileId();
			});

			$propFind->handle(self::OBJECTIDS_PROPERTYNAME, function () use ($node): SystemTagsObjectList {
				$objectTypes = $this->tagMapper->getAvailableObjectTypes();
				$objects = [];
				foreach ($objectTypes as $type) {
					$systemTagObjectType = new SystemTagObjectType($node->getSystemTag(), $type, $this->tagManager, $this->tagMapper);
					$objects = array_merge($objects, array_fill_keys($systemTagObjectType->getObjectsIds(), $type));
				}
				return new SystemTagsObjectList($objects);
			});
		}

		if ($node instanceof SystemTagObjectType) {
			$propFind->handle(self::OBJECTIDS_PROPERTYNAME, function () use ($node): SystemTagsObjectList {
				return new SystemTagsObjectList(array_fill_keys($node->getObjectsIds(), $node->getName()));
			});
		}
	}

	private function propfindForFile(PropFind $propFind, Node $node): void {

		$propFind->handle(self::SYSTEM_TAGS_PROPERTYNAME, function () use ($node) {
			$user = $this->userSession->getUser();

			$tags = $this->getTagsForFile($node->getId(), $user);
			usort($tags, function (ISystemTag $tagA, ISystemTag $tagB): int {
				return Util::naturalSortCompare($tagA->getName(), $tagB->getName());
			});
			return new SystemTagList($tags, $this->tagManager, $user);
		});
	}

	/**
	 * @param int $fileId
	 * @return ISystemTag[]
	 */
	private function getTagsForFile(int $fileId, ?IUser $user): array {
		if (isset($this->cachedTagMappings[$fileId])) {
			$tagIds = $this->cachedTagMappings[$fileId];
		} else {
			$tags = $this->tagMapper->getTagIdsForObjects([$fileId], 'files');
			$fileTags = current($tags);
			if ($fileTags) {
				$tagIds = $fileTags;
			} else {
				$tagIds = [];
			}
		}

		$tags = array_filter(array_map(function (string $tagId) {
			return $this->cachedTags[$tagId] ?? null;
		}, $tagIds));

		$uncachedTagIds = array_filter($tagIds, function (string $tagId): bool {
			return !isset($this->cachedTags[$tagId]);
		});

		if (count($uncachedTagIds)) {
			$retrievedTags = $this->tagManager->getTagsByIds($uncachedTagIds);
			foreach ($retrievedTags as $tag) {
				$this->cachedTags[$tag->getId()] = $tag;
			}
			$tags += $retrievedTags;
		}

		return array_filter($tags, function (ISystemTag $tag) use ($user) {
			return $this->tagManager->canUserSeeTag($tag, $user);
		});
	}

	/**
	 * Updates tag attributes
	 *
	 * @param string $path
	 * @param PropPatch $propPatch
	 *
	 * @return void
	 */
	public function handleUpdateProperties($path, PropPatch $propPatch) {
		$node = $this->server->tree->getNodeForPath($path);
		if (!$node instanceof SystemTagNode && !$node instanceof SystemTagObjectType) {
			return;
		}

		$propPatch->handle([self::OBJECTIDS_PROPERTYNAME], function ($props) use ($node) {
			if (!$node instanceof SystemTagObjectType) {
				return false;
			}

			if (isset($props[self::OBJECTIDS_PROPERTYNAME])) {
				$user = $this->userSession->getUser();
				if (!$user) {
					throw new Forbidden('You don’t have permissions to update tags');
				}

				$propValue = $props[self::OBJECTIDS_PROPERTYNAME];
				if (!$propValue instanceof SystemTagsObjectList || count($propValue->getObjects()) === 0) {
					throw new BadRequest('Invalid object-ids property');
				}

				$objects = $propValue->getObjects();
				$objectTypes = array_unique(array_values($objects));

				if (count($objectTypes) !== 1 || $objectTypes[0] !== $node->getName()) {
					throw new BadRequest('Invalid object-ids property. All object types must be of the same type: ' . $node->getName());
				}

				// Only files are supported at the moment
				// Also see SystemTagsRelationsCollection file
				if ($objectTypes[0] !== 'files') {
					throw new BadRequest('Invalid object-ids property type. Only files are supported');
				}

				// Get all current tagged objects
				$taggedObjects = $this->tagMapper->getObjectIdsForTags([$node->getSystemTag()->getId()], 'files');
				$toAddObjects = array_map(fn ($value) => (string)$value, array_keys($objects));

				// Compute the tags to add and remove
				$addedObjects = array_values(array_diff($toAddObjects, $taggedObjects));
				$removedObjects = array_values(array_diff($taggedObjects, $toAddObjects));

				// Check permissions for each object to be freshly tagged or untagged
				if (!$this->canUpdateTagForFileIds(array_merge($addedObjects, $removedObjects))) {
					throw new Forbidden('You don’t have permissions to update tags');
				}

				$this->tagMapper->setObjectIdsForTag($node->getSystemTag()->getId(), $node->getName(), array_keys($objects));
			}

			if ($props[self::OBJECTIDS_PROPERTYNAME] === null) {
				// Check the user have permissions to remove the tag from all currently tagged objects
				$taggedObjects = $this->tagMapper->getObjectIdsForTags([$node->getSystemTag()->getId()], 'files');
				if (!$this->canUpdateTagForFileIds($taggedObjects)) {
					throw new Forbidden('You don’t have permissions to update tags');
				}

				$this->tagMapper->setObjectIdsForTag($node->getSystemTag()->getId(), $node->getName(), []);
			}

			return true;
		});

		$propPatch->handle([
			self::DISPLAYNAME_PROPERTYNAME,
			self::USERVISIBLE_PROPERTYNAME,
			self::USERASSIGNABLE_PROPERTYNAME,
			self::GROUPS_PROPERTYNAME,
			self::NUM_FILES_PROPERTYNAME,
			self::REFERENCE_FILEID_PROPERTYNAME,
			self::COLOR_PROPERTYNAME,
		], function ($props) use ($node) {
			if (!$node instanceof SystemTagNode) {
				return false;
			}

			$tag = $node->getSystemTag();
			$name = $tag->getName();
			$userVisible = $tag->isUserVisible();
			$userAssignable = $tag->isUserAssignable();
			$color = $tag->getColor();

			$updateTag = false;

			if (isset($props[self::DISPLAYNAME_PROPERTYNAME])) {
				$name = $props[self::DISPLAYNAME_PROPERTYNAME];
				$updateTag = true;
			}

			if (isset($props[self::USERVISIBLE_PROPERTYNAME])) {
				$propValue = $props[self::USERVISIBLE_PROPERTYNAME];
				$userVisible = ($propValue !== 'false' && $propValue !== '0');
				$updateTag = true;
			}

			if (isset($props[self::USERASSIGNABLE_PROPERTYNAME])) {
				$propValue = $props[self::USERASSIGNABLE_PROPERTYNAME];
				$userAssignable = ($propValue !== 'false' && $propValue !== '0');
				$updateTag = true;
			}

			if (isset($props[self::COLOR_PROPERTYNAME])) {
				$propValue = $props[self::COLOR_PROPERTYNAME];
				if ($propValue === '' || $propValue === 'null') {
					$propValue = null;
				}
				$color = $propValue;
				$updateTag = true;
			}

			if (isset($props[self::GROUPS_PROPERTYNAME])) {
				if (!$this->groupManager->isAdmin($this->userSession->getUser()->getUID())) {
					// property only available for admins
					throw new Forbidden();
				}

				$propValue = $props[self::GROUPS_PROPERTYNAME];
				$groupIds = explode('|', $propValue);
				$this->tagManager->setTagGroups($tag, $groupIds);
			}

			if (isset($props[self::NUM_FILES_PROPERTYNAME]) || isset($props[self::REFERENCE_FILEID_PROPERTYNAME])) {
				// read-only properties
				throw new Forbidden();
			}

			if ($updateTag) {
				try {
					$node->update($name, $userVisible, $userAssignable, $color);
				} catch (TagUpdateForbiddenException $e) {
					throw new Forbidden('You don’t have permissions to update tags', 0, $e);
				}
			}

			return true;
		});
	}

	/**
	 * Check if the user can update the tag for the given file ids
	 *
	 * @param list<string> $fileIds
	 * @return bool
	 */
	private function canUpdateTagForFileIds(array $fileIds): bool {
		$user = $this->userSession->getUser();
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());

		foreach ($fileIds as $fileId) {
			try {
				$nodes = $userFolder->getById((int)$fileId);
				if (empty($nodes)) {
					return false;
				}

				foreach ($nodes as $node) {
					if (($node->getPermissions() & Constants::PERMISSION_UPDATE) !== Constants::PERMISSION_UPDATE) {
						return false;
					}
				}
			} catch (\Exception $e) {
				return false;
			}
		}

		return true;
	}
}

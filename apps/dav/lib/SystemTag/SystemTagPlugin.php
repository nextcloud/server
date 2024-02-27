<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\SystemTag;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Node;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagAlreadyExistsException;
use OCP\Util;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\UnsupportedMediaType;
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
	public const ID_PROPERTYNAME = '{http://owncloud.org/ns}id';
	public const DISPLAYNAME_PROPERTYNAME = '{http://owncloud.org/ns}display-name';
	public const USERVISIBLE_PROPERTYNAME = '{http://owncloud.org/ns}user-visible';
	public const USERASSIGNABLE_PROPERTYNAME = '{http://owncloud.org/ns}user-assignable';
	public const GROUPS_PROPERTYNAME = '{http://owncloud.org/ns}groups';
	public const CANASSIGN_PROPERTYNAME = '{http://owncloud.org/ns}can-assign';
	public const SYSTEM_TAGS_PROPERTYNAME = '{http://nextcloud.org/ns}system-tags';
	public const NUM_FILES_PROPERTYNAME = '{http://nextcloud.org/ns}files-assigned';
	public const FILEID_PROPERTYNAME = '{http://nextcloud.org/ns}reference-fileid';

	/**
	 * @var \Sabre\DAV\Server $server
	 */
	private $server;

	/**
	 * @var ISystemTagManager
	 */
	protected $tagManager;

	/**
	 * @var IUserSession
	 */
	protected $userSession;

	/**
	 * @var IGroupManager
	 */
	protected $groupManager;

	/** @var array<int, string[]> */
	private array $cachedTagMappings = [];
	/** @var array<string, ISystemTag> */
	private array $cachedTags = [];

	private ISystemTagObjectMapper $tagMapper;

	public function __construct(
		ISystemTagManager $tagManager,
		IGroupManager $groupManager,
		IUserSession $userSession,
		ISystemTagObjectMapper $tagMapper,
	) {
		$this->tagManager = $tagManager;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->tagMapper = $tagMapper;
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

		$server->protectedProperties[] = self::ID_PROPERTYNAME;

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
			$response->setStatus(201);
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
		\Sabre\DAV\INode $node
	) {
		if ($node instanceof Node) {
			$this->propfindForFile($propFind, $node);
			return;
		}

		if (!($node instanceof SystemTagNode) && !($node instanceof SystemTagMappingNode)) {
			return;
		}

		// child nodes from systemtags-assigned should point to normal tag endpoint
		if (preg_match('/^systemtags-assigned\/[0-9]+/', $propFind->getPath())) {
			$propFind->setPath(str_replace('systemtags-assigned/', 'systemtags/', $propFind->getPath()));
		}

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

			$propFind->handle(self::FILEID_PROPERTYNAME, function () use ($node): int {
				return $node->getReferenceFileId();
			});
		}
	}

	private function propfindForFile(PropFind $propFind, Node $node): void {
		if ($node instanceof Directory
			&& $propFind->getDepth() !== 0
			&& !is_null($propFind->getStatus(self::SYSTEM_TAGS_PROPERTYNAME))) {
			$fileIds = [$node->getId()];

			// note: pre-fetching only supported for depth <= 1
			$folderContent = $node->getChildren();
			foreach ($folderContent as $info) {
				if ($info instanceof Node) {
					$fileIds[] = $info->getId();
				}
			}

			$tags = $this->tagMapper->getTagIdsForObjects($fileIds, 'files');

			$this->cachedTagMappings = $this->cachedTagMappings + $tags;
			$emptyFileIds = array_diff($fileIds, array_keys($tags));

			// also cache the ones that were not found
			foreach ($emptyFileIds as $fileId) {
				$this->cachedTagMappings[$fileId] = [];
			}
		}

		$propFind->handle(self::SYSTEM_TAGS_PROPERTYNAME, function () use ($node) {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return;
			}

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
	private function getTagsForFile(int $fileId, IUser $user): array {

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
		if (!($node instanceof SystemTagNode)) {
			return;
		}

		$propPatch->handle([
			self::DISPLAYNAME_PROPERTYNAME,
			self::USERVISIBLE_PROPERTYNAME,
			self::USERASSIGNABLE_PROPERTYNAME,
			self::GROUPS_PROPERTYNAME,
			self::NUM_FILES_PROPERTYNAME,
			self::FILEID_PROPERTYNAME,
		], function ($props) use ($node) {
			$tag = $node->getSystemTag();
			$name = $tag->getName();
			$userVisible = $tag->isUserVisible();
			$userAssignable = $tag->isUserAssignable();

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

			if (isset($props[self::GROUPS_PROPERTYNAME])) {
				if (!$this->groupManager->isAdmin($this->userSession->getUser()->getUID())) {
					// property only available for admins
					throw new Forbidden();
				}

				$propValue = $props[self::GROUPS_PROPERTYNAME];
				$groupIds = explode('|', $propValue);
				$this->tagManager->setTagGroups($tag, $groupIds);
			}

			if (isset($props[self::NUM_FILES_PROPERTYNAME]) || isset($props[self::FILEID_PROPERTYNAME])) {
				// read-only properties
				throw new Forbidden();
			}

			if ($updateTag) {
				$node->update($name, $userVisible, $userAssignable);
			}

			return true;
		});
	}
}

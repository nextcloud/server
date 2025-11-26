<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\SystemTag;

use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ManagerEvent;
use OCP\SystemTag\TagAlreadyExistsException;
use OCP\SystemTag\TagCreationForbiddenException;
use OCP\SystemTag\TagNotFoundException;
use OCP\SystemTag\TagUpdateForbiddenException;

/**
 * Manager class for system tags
 */
class SystemTagManager implements ISystemTagManager {
	public const TAG_TABLE = 'systemtag';
	public const TAG_GROUP_TABLE = 'systemtag_group';

	/**
	 * Prepared query for selecting tags directly
	 */
	private IQueryBuilder $selectTagQuery;

	public function __construct(
		protected IDBConnection $connection,
		protected IGroupManager $groupManager,
		protected IEventDispatcher $dispatcher,
		private IUserSession $userSession,
		private IAppConfig $appConfig,
	) {
		$query = $this->connection->getQueryBuilder();
		$this->selectTagQuery = $query->select('*')
			->from(self::TAG_TABLE)
			->where($query->expr()->eq('name', $query->createParameter('name')))
			->andWhere($query->expr()->eq('visibility', $query->createParameter('visibility')))
			->andWhere($query->expr()->eq('editable', $query->createParameter('editable')));
	}

	public function getTagsByIds($tagIds, ?IUser $user = null): array {
		if (!\is_array($tagIds)) {
			$tagIds = [$tagIds];
		}

		$tags = [];

		// note: not all databases will fail if it's a string or starts with a number
		foreach ($tagIds as $tagId) {
			if (!is_numeric($tagId)) {
				throw new \InvalidArgumentException('Tag id must be integer');
			}
		}

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from(self::TAG_TABLE)
			->where($query->expr()->in('id', $query->createParameter('tagids')))
			->addOrderBy('name', 'ASC')
			->addOrderBy('visibility', 'ASC')
			->addOrderBy('editable', 'ASC')
			->setParameter('tagids', $tagIds, IQueryBuilder::PARAM_INT_ARRAY);

		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			$tag = $this->createSystemTagFromRow($row);
			if ($user && !$this->canUserSeeTag($tag, $user)) {
				// if a user is given, hide invisible tags
				continue;
			}
			$tags[$row['id']] = $tag;
		}

		$result->closeCursor();

		if (\count($tags) !== \count($tagIds)) {
			throw new TagNotFoundException(
				'Tag id(s) not found', 0, null, array_diff($tagIds, array_keys($tags))
			);
		}

		return $tags;
	}

	public function getAllTags($visibilityFilter = null, $nameSearchPattern = null): array {
		$tags = [];

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from(self::TAG_TABLE);

		if (!\is_null($visibilityFilter)) {
			$query->andWhere($query->expr()->eq('visibility', $query->createNamedParameter((int)$visibilityFilter)));
		}

		if (!empty($nameSearchPattern)) {
			$query->andWhere(
				$query->expr()->iLike(
					'name',
					$query->createNamedParameter('%' . $this->connection->escapeLikeParameter($nameSearchPattern) . '%')
				)
			);
		}

		$query
			->addOrderBy('name', 'ASC')
			->addOrderBy('visibility', 'ASC')
			->addOrderBy('editable', 'ASC');

		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			$tags[$row['id']] = $this->createSystemTagFromRow($row);
		}

		$result->closeCursor();

		return $tags;
	}

	public function getTag(string $tagName, bool $userVisible, bool $userAssignable): ISystemTag {
		// Length of name column is 64
		$truncatedTagName = substr($tagName, 0, 64);
		$result = $this->selectTagQuery
			->setParameter('name', $truncatedTagName)
			->setParameter('visibility', $userVisible ? 1 : 0)
			->setParameter('editable', $userAssignable ? 1 : 0)
			->executeQuery();

		$row = $result->fetch();
		$result->closeCursor();
		if (!$row) {
			throw new TagNotFoundException(
				'Tag ("' . $truncatedTagName . '", ' . $userVisible . ', ' . $userAssignable . ') does not exist'
			);
		}

		return $this->createSystemTagFromRow($row);
	}

	public function createTag(string $tagName, bool $userVisible, bool $userAssignable): ISystemTag {
		$user = $this->userSession->getUser();
		if (!$this->canUserCreateTag($user)) {
			throw new TagCreationForbiddenException();
		}

		// Check if tag already exists (case-insensitive)
		$existingTags = $this->getAllTags(null, $tagName);
		foreach ($existingTags as $existingTag) {
			if (mb_strtolower($existingTag->getName()) === mb_strtolower($tagName)) {
				throw new TagAlreadyExistsException('Tag ' . $tagName . ' already exists');
			}
		}

		// Length of name column is 64
		$truncatedTagName = substr($tagName, 0, 64);
		$query = $this->connection->getQueryBuilder();
		$query->insert(self::TAG_TABLE)
			->values([
				'name' => $query->createNamedParameter($truncatedTagName),
				'visibility' => $query->createNamedParameter($userVisible ? 1 : 0),
				'editable' => $query->createNamedParameter($userAssignable ? 1 : 0),
				'etag' => $query->createNamedParameter(md5((string)time())),
			]);

		try {
			$query->executeStatement();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new TagAlreadyExistsException(
					'Tag ("' . $truncatedTagName . '", ' . $userVisible . ', ' . $userAssignable . ') already exists',
					0,
					$e
				);
			}
			throw $e;
		}

		$tagId = $query->getLastInsertId();

		$tag = new SystemTag(
			(string)$tagId,
			$truncatedTagName,
			$userVisible,
			$userAssignable
		);

		$this->dispatcher->dispatch(ManagerEvent::EVENT_CREATE, new ManagerEvent(
			ManagerEvent::EVENT_CREATE, $tag
		));

		return $tag;
	}

	public function updateTag(
		string $tagId,
		string $newName,
		bool $userVisible,
		bool $userAssignable,
		?string $color,
	): void {
		try {
			$tags = $this->getTagsByIds($tagId);
		} catch (TagNotFoundException $e) {
			throw new TagNotFoundException(
				'Tag does not exist', 0, null, [$tagId]
			);
		}

		$user = $this->userSession->getUser();
		if (!$this->canUserUpdateTag($user)) {
			throw new TagUpdateForbiddenException();
		}

		$beforeUpdate = array_shift($tags);
		// Length of name column is 64
		$newName = trim($newName);
		$truncatedNewName = substr($newName, 0, 64);
		$afterUpdate = new SystemTag(
			$tagId,
			$truncatedNewName,
			$userVisible,
			$userAssignable,
			$beforeUpdate->getETag(),
			$color
		);

		// Check if tag already exists (case-insensitive)
		$existingTags = $this->getAllTags(null, $truncatedNewName);
		foreach ($existingTags as $existingTag) {
			if (mb_strtolower($existingTag->getName()) === mb_strtolower($truncatedNewName)
				&& $existingTag->getId() !== $tagId) {
				throw new TagAlreadyExistsException('Tag ' . $truncatedNewName . ' already exists');
			}
		}

		$query = $this->connection->getQueryBuilder();
		$query->update(self::TAG_TABLE)
			->set('name', $query->createParameter('name'))
			->set('visibility', $query->createParameter('visibility'))
			->set('editable', $query->createParameter('editable'))
			->set('color', $query->createParameter('color'))
			->where($query->expr()->eq('id', $query->createParameter('tagid')))
			->setParameter('name', $truncatedNewName)
			->setParameter('visibility', $userVisible ? 1 : 0)
			->setParameter('editable', $userAssignable ? 1 : 0)
			->setParameter('tagid', $tagId)
			->setParameter('color', $color);

		try {
			if ($query->executeStatement() === 0) {
				throw new TagNotFoundException(
					'Tag does not exist', 0, null, [$tagId]
				);
			}
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new TagAlreadyExistsException(
					'Tag ("' . $newName . '", ' . $userVisible . ', ' . $userAssignable . ') already exists',
					0,
					$e
				);
			}
			throw $e;
		}

		$this->dispatcher->dispatch(ManagerEvent::EVENT_UPDATE, new ManagerEvent(
			ManagerEvent::EVENT_UPDATE, $afterUpdate, $beforeUpdate
		));
	}

	public function deleteTags($tagIds): void {
		if (!\is_array($tagIds)) {
			$tagIds = [$tagIds];
		}

		$tagNotFoundException = null;
		$tags = [];
		try {
			$tags = $this->getTagsByIds($tagIds);
		} catch (TagNotFoundException $e) {
			$tagNotFoundException = $e;

			// Get existing tag objects for the hooks later
			$existingTags = array_diff($tagIds, $tagNotFoundException->getMissingTags());
			if (!empty($existingTags)) {
				try {
					$tags = $this->getTagsByIds($existingTags);
				} catch (TagNotFoundException $e) {
					// Ignore further errors...
				}
			}
		}

		// delete relationships first
		$query = $this->connection->getQueryBuilder();
		$query->delete(SystemTagObjectMapper::RELATION_TABLE)
			->where($query->expr()->in('systemtagid', $query->createParameter('tagids')))
			->setParameter('tagids', $tagIds, IQueryBuilder::PARAM_INT_ARRAY)
			->executeStatement();

		$query = $this->connection->getQueryBuilder();
		$query->delete(self::TAG_TABLE)
			->where($query->expr()->in('id', $query->createParameter('tagids')))
			->setParameter('tagids', $tagIds, IQueryBuilder::PARAM_INT_ARRAY)
			->executeStatement();

		foreach ($tags as $tag) {
			$this->dispatcher->dispatch(ManagerEvent::EVENT_DELETE, new ManagerEvent(
				ManagerEvent::EVENT_DELETE, $tag
			));
		}

		if ($tagNotFoundException !== null) {
			throw new TagNotFoundException(
				'Tag id(s) not found', 0, $tagNotFoundException, $tagNotFoundException->getMissingTags()
			);
		}
	}

	public function canUserAssignTag(ISystemTag $tag, ?IUser $user): bool {
		if ($user === null) {
			return false;
		}

		// early check to avoid unneeded group lookups
		if ($tag->isUserAssignable() && $tag->isUserVisible()) {
			return true;
		}

		if ($this->groupManager->isAdmin($user->getUID())) {
			return true;
		}

		if (!$tag->isUserVisible()) {
			return false;
		}

		$groupIds = $this->groupManager->getUserGroupIds($user);
		if (!empty($groupIds)) {
			$matchingGroups = array_intersect($groupIds, $this->getTagGroups($tag));
			if (!empty($matchingGroups)) {
				return true;
			}
		}

		return false;
	}

	public function canUserCreateTag(?IUser $user): bool {
		if ($user === null) {
			// If no user given, allows only calls from CLI
			return \OC::$CLI;
		}

		if ($this->appConfig->getValueBool('systemtags', 'restrict_creation_to_admin', false) === false) {
			return true;
		}

		return $this->groupManager->isAdmin($user->getUID());
	}

	public function canUserUpdateTag(?IUser $user): bool {
		// We currently have no different permissions for updating tags than for creating them
		return $this->canUserCreateTag($user);
	}

	public function canUserSeeTag(ISystemTag $tag, ?IUser $user): bool {
		// If no user, then we only show public tags
		if (!$user && $tag->getAccessLevel() === ISystemTag::ACCESS_LEVEL_PUBLIC) {
			return true;
		}

		if ($tag->isUserVisible()) {
			return true;
		}

		// if not returned yet, and user is not logged in, then the tag is not visible
		if ($user === null) {
			return false;
		}

		if ($this->groupManager->isAdmin($user->getUID())) {
			return true;
		}

		return false;
	}

	private function createSystemTagFromRow($row): SystemTag {
		return new SystemTag((string)$row['id'], $row['name'], (bool)$row['visibility'], (bool)$row['editable'], $row['etag'], $row['color']);
	}

	public function setTagGroups(ISystemTag $tag, array $groupIds): void {
		// delete relationships first
		$this->connection->beginTransaction();
		try {
			$query = $this->connection->getQueryBuilder();
			$query->delete(self::TAG_GROUP_TABLE)
				->where($query->expr()->eq('systemtagid', $query->createNamedParameter($tag->getId())))
				->executeStatement();

			// add each group id
			$query = $this->connection->getQueryBuilder();
			$query->insert(self::TAG_GROUP_TABLE)
				->values([
					'systemtagid' => $query->createNamedParameter($tag->getId()),
					'gid' => $query->createParameter('gid'),
				]);
			foreach ($groupIds as $groupId) {
				if ($groupId === '') {
					continue;
				}
				$query->setParameter('gid', $groupId);
				$query->executeStatement();
			}

			$this->connection->commit();
		} catch (\Exception $e) {
			$this->connection->rollBack();
			throw $e;
		}
	}

	public function getTagGroups(ISystemTag $tag): array {
		$groupIds = [];
		$query = $this->connection->getQueryBuilder();
		$query->select('gid')
			->from(self::TAG_GROUP_TABLE)
			->where($query->expr()->eq('systemtagid', $query->createNamedParameter($tag->getId())))
			->orderBy('gid');

		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			$groupIds[] = $row['gid'];
		}

		$result->closeCursor();

		return $groupIds;
	}

}

<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC;

use OC\Tagging\Tag;
use OC\Tagging\TagMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\NodeAddedToFavorite;
use OCP\Files\Events\NodeRemovedFromFavorite;
use OCP\Files\Folder;
use OCP\IDBConnection;
use OCP\ITags;
use OCP\IUserManager;
use OCP\Server;
use OCP\Util;
use Psr\Log\LoggerInterface;

class Tags implements ITags {
	/**
	 * Used for storing objectid/categoryname pairs while rescanning.
	 */
	private array $relations = [];
	/** @var list<Tag> $tags */
	private array $tags = [];

	/**
	 * The current user, plus any owners of the items shared with the current
	 * user, if $this->includeShared === true.
	 * @var list<string> $owners
	 */
	private array $owners = [];

	public const string TAG_TABLE = 'vcategory';
	public const string RELATION_TABLE = 'vcategory_to_object';

	/**
	 * Constructor.
	 *
	 * @param TagMapper $mapper Instance of the TagMapper abstraction layer.
	 * @param string $user The user whose data the object will operate on.
	 * @param string $type The type of items for which tags will be loaded.
	 * @param array $defaultTags Tags that should be created at construction.
	 *
	 * since 20.0.0 $includeShared isn't used anymore
	 */
	public function __construct(
		private TagMapper $mapper,
		private string $user,
		private string $type,
		private LoggerInterface $logger,
		private IDBConnection $db,
		private IEventDispatcher $dispatcher,
		private IUserManager $userManager,
		private Folder $userFolder,
		array $defaultTags = [],
	) {
		$this->owners = [$this->user];
		$this->tags = $this->mapper->loadTags($this->owners, $this->type);

		if (count($defaultTags) > 0 && count($this->tags) === 0) {
			$this->addMultiple($defaultTags, true);
		}
	}

	#[\Override]
	public function isEmpty(): bool {
		return count($this->tags) === 0;
	}

	#[\Override]
	public function getTag(string $id): array|false {
		$key = $this->getTagById($id);
		if ($key !== false) {
			return $this->tagMap($this->tags[$key]);
		}
		return false;
	}

	#[\Override]
	public function getTags(): array {
		if (!count($this->tags)) {
			return [];
		}

		usort($this->tags, function (Tag $a, Tag $b): int {
			return strnatcasecmp($a->name, $b->name);
		});
		$tagMap = [];

		foreach ($this->tags as $tag) {
			if ($tag->name !== ITags::TAG_FAVORITE) {
				$tagMap[] = $this->tagMap($tag);
			}
		}
		return $tagMap;
	}

	/**
	 * Return only the tags owned by the given user, omitting any tags shared
	 * by other users.
	 *
	 * @param string $user The user whose tags are to be checked.
	 * @return array An array of Tag objects.
	 */
	public function getTagsForUser(string $user): array {
		return array_filter($this->tags,
			function (Tag $tag) use ($user) {
				return $tag->owner === $user;
			}
		);
	}

	/**
	 * Get the list of tags for the given ids.
	 *
	 * @param list<int> $objIds array of object ids
	 * @return array<int, list<string>>|false of tags id as key to array of tag names
	 *                                        or false if an error occurred
	 */
	#[\Override]
	public function getTagsForObjects(array $objIds): array|false {
		$entries = [];

		try {
			$chunks = array_chunk($objIds, 900, false);
			$qb = $this->db->getQueryBuilder();
			$qb->select('category', 'categoryid', 'objid')
				->from(self::RELATION_TABLE, 'r')
				->join('r', self::TAG_TABLE, 't', $qb->expr()->eq('r.categoryid', 't.id'))
				->where($qb->expr()->eq('uid', $qb->createParameter('uid')))
				->andWhere($qb->expr()->eq('r.type', $qb->createParameter('type')))
				->andWhere($qb->expr()->in('objid', $qb->createParameter('chunk')));
			foreach ($chunks as $chunk) {
				$qb->setParameter('uid', $this->user, IQueryBuilder::PARAM_STR);
				$qb->setParameter('type', $this->type, IQueryBuilder::PARAM_STR);
				$qb->setParameter('chunk', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
				$result = $qb->executeQuery();
				while ($row = $result->fetchAssociative()) {
					$objId = (int)$row['objid'];
					if (!isset($entries[$objId])) {
						$entries[$objId] = [];
					}
					$entries[$objId][] = (string)$row['category'];
				}
				$result->closeCursor();
			}
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), [
				'exception' => $e,
				'app' => 'core',
			]);
			return false;
		}

		return $entries;
	}

	#[\Override]
	public function getIdsForTag(int|string $tag): array|false {
		if (is_numeric($tag)) {
			$tagId = $tag;
		} else {
			$tag = trim($tag);
			if ($tag === '') {
				$this->logger->debug(__METHOD__ . ' Cannot use empty tag names', ['app' => 'core']);
				return false;
			}
			$tagId = $this->getTagId($tag);
		}

		if ($tagId === false) {
			$l10n = Util::getL10N('core');
			throw new \Exception(
				$l10n->t('Could not find category "%s"', [$tag])
			);
		}

		$ids = [];
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select('objid')
				->from(self::RELATION_TABLE)
				->where($qb->expr()->eq('categoryid', $qb->createNamedParameter($tagId, IQueryBuilder::PARAM_STR)));
			$result = $qb->executeQuery();
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), [
				'app' => 'core',
				'exception' => $e,
			]);
			return false;
		}

		while ($row = $result->fetchAssociative()) {
			$ids[] = (int)$row['objid'];
		}
		$result->closeCursor();

		return $ids;
	}

	#[\Override]
	public function userHasTag(string $name, string $user): bool {
		return $this->array_searchi($name, $this->getTagsForUser($user)) !== false;
	}

	#[\Override]
	public function hasTag(string $name): bool {
		return $this->getTagId($name) !== false;
	}

	#[\Override]
	public function add(string $name): false|int {
		$name = trim($name);

		if ($name === '') {
			$this->logger->debug(__METHOD__ . ' Cannot add an empty tag', ['app' => 'core']);
			return false;
		}
		if ($this->userHasTag($name, $this->user)) {
			$this->logger->debug(__METHOD__ . ' Tag with name already exists', ['app' => 'core']);
			return false;
		}
		try {
			$tag = new Tag();
			$tag->owner = $this->user;
			$tag->type = $this->type;
			$tag->name = $name;
			$tag = $this->mapper->insert($tag);
			$this->tags[] = $tag;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), [
				'exception' => $e,
				'app' => 'core',
			]);
			return false;
		}
		$this->logger->debug(__METHOD__ . ' Added an tag with ' . $tag->id, ['app' => 'core']);
		return $tag->id ?? false;
	}

	#[\Override]
	public function rename(string|int $from, string $to): bool {
		$from = trim((string)$from);
		$to = trim($to);

		if ($to === '' || $from === '') {
			$this->logger->debug(__METHOD__ . 'Cannot use an empty tag names', ['app' => 'core']);
			return false;
		}

		if (is_numeric($from)) {
			$key = $this->getTagById($from);
		} else {
			$key = $this->getTagByName($from);
		}
		if ($key === false) {
			$this->logger->debug(__METHOD__ . 'Tag ' . $from . 'does not exist', ['app' => 'core']);
			return false;
		}
		$tag = $this->tags[$key];

		if ($this->userHasTag($to, $tag->owner)) {
			$this->logger->debug(__METHOD__ . 'A tag named' . $to . 'already exists for user' . $tag->owner, ['app' => 'core']);
			return false;
		}

		try {
			$tag->name = $to;
			$this->tags[$key] = $this->mapper->update($tag);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), [
				'exception' => $e,
				'app' => 'core',
			]);
			return false;
		}
		return true;
	}

	#[\Override]
	public function addMultiple(string|array $names, bool $sync = false, ?int $id = null): bool {
		if (!is_array($names)) {
			$names = [$names];
		}
		$names = array_map('trim', $names);
		array_filter($names);

		$newones = [];
		foreach ($names as $name) {
			if (!$this->hasTag($name) && $name !== '') {
				$tag = new Tag();
				$tag->owner = $this->user;
				$tag->type = $this->type;
				$tag->name = $name;
				$newones[] = $tag;
			}
			if (!is_null($id)) {
				// Insert $objectid, $categoryid  pairs if not exist.
				$this->relations[] = ['objid' => $id, 'tag' => $name];
			}
		}
		$this->tags = array_merge($this->tags, $newones);
		if ($sync === true) {
			$this->save();
		}

		return true;
	}

	/**
	 * Save the list of tags and their object relations
	 */
	protected function save(): void {
		foreach ($this->tags as $tag) {
			try {
				$this->mapper->insert($tag);
			} catch (Exception $e) {
				if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					$this->logger->error($e->getMessage(), [
						'exception' => $e,
						'app' => 'core',
					]);
				}
			}
		}

		// reload tags to get the proper ids.
		$this->tags = $this->mapper->loadTags($this->owners, $this->type);
		$this->logger->debug(__METHOD__ . 'tags' . print_r($this->tags, true), ['app' => 'core']);
		// Loop through temporarily cached objectid/tagname pairs
		// and save relations.
		foreach ($this->relations as $relation) {
			$tagId = $this->getTagId($relation['tag']);
			$this->logger->debug(__METHOD__ . 'catid ' . $relation['tag'] . ' ' . $tagId, ['app' => 'core']);
			if ($tagId) {
				$qb = $this->db->getQueryBuilder();
				$qb->insert(self::RELATION_TABLE)
					->values([
						'objid' => $qb->createNamedParameter($relation['objid'], IQueryBuilder::PARAM_INT),
						'categoryid' => $qb->createNamedParameter($tagId, IQueryBuilder::PARAM_INT),
						'type' => $qb->createNamedParameter($this->type),
					]);
				try {
					$qb->executeStatement();
				} catch (Exception $e) {
					$this->logger->error($e->getMessage(), [
						'exception' => $e,
						'app' => 'core',
					]);
				}
			}
		}
		$this->relations = []; // reset
	}

	/**
	 * Delete tag/object relations from the db
	 *
	 * @param array $ids The ids of the objects
	 * @return boolean Returns false on error.
	 */
	#[\Override]
	public function purgeObjects(array $ids): bool {
		if (count($ids) === 0) {
			// job done ;)
			return true;
		}
		$updates = $ids;
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::RELATION_TABLE)
			->where($qb->expr()->in('objid', $qb->createNamedParameter($ids)));
		try {
			$qb->executeStatement();
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), [
				'app' => 'core',
				'exception' => $e,
			]);
			return false;
		}
		return true;
	}

	#[\Override]
	public function getFavorites(): array|false {
		if (!$this->userHasTag(ITags::TAG_FAVORITE, $this->user)) {
			return [];
		}

		try {
			return $this->getIdsForTag(ITags::TAG_FAVORITE);
		} catch (\Exception $e) {
			Server::get(LoggerInterface::class)->error(
				$e->getMessage(),
				[
					'app' => 'core',
					'exception' => $e,
				]
			);
			return [];
		}
	}

	#[\Override]
	public function addToFavorites($objid): bool {
		if (!$this->userHasTag(ITags::TAG_FAVORITE, $this->user)) {
			$this->add(ITags::TAG_FAVORITE);
		}
		return $this->tagAs($objid, ITags::TAG_FAVORITE);
	}

	#[\Override]
	public function removeFromFavorites($objid): bool {
		return $this->unTag($objid, ITags::TAG_FAVORITE);
	}

	#[\Override]
	public function tagAs($objid, $tag, ?string $path = null): bool {
		if (is_string($tag) && !is_numeric($tag)) {
			$tag = trim($tag);
			if ($tag === '') {
				$this->logger->debug(__METHOD__ . ', Cannot add an empty tag');
				return false;
			}
			if (!$this->hasTag($tag)) {
				$this->add($tag);
			}
			$tagId = $this->getTagId($tag);
		} else {
			$tagId = $tag;
		}
		$qb = $this->db->getQueryBuilder();
		$qb->insert(self::RELATION_TABLE)
			->values([
				'objid' => $qb->createNamedParameter($objid, IQueryBuilder::PARAM_INT),
				'categoryid' => $qb->createNamedParameter($tagId, IQueryBuilder::PARAM_INT),
				'type' => $qb->createNamedParameter($this->type, IQueryBuilder::PARAM_STR),
			]);
		try {
			$qb->executeStatement();
		} catch (\Exception $e) {
			Server::get(LoggerInterface::class)->error($e->getMessage(), [
				'app' => 'core',
				'exception' => $e,
			]);
			return false;
		}
		if ($tag === ITags::TAG_FAVORITE) {
			if ($path === null) {
				$node = $this->userFolder->getFirstNodeById($objid);
				if ($node !== null) {
					$path = $node->getPath();
				} else {
					throw new Exception('Failed to favorite: node with id ' . $objid . ' not found');
				}
			}

			$this->dispatcher->dispatchTyped(new NodeAddedToFavorite($this->userManager->getExistingUser($this->user), $objid, $path));
		}
		return true;
	}

	#[\Override]
	public function unTag($objid, $tag, ?string $path = null): bool {
		if (is_string($tag) && !is_numeric($tag)) {
			$tag = trim($tag);
			if ($tag === '') {
				$this->logger->debug(__METHOD__ . ', Tag name is empty');
				return false;
			}
			$tagId = $this->getTagId($tag);
		} else {
			$tagId = $tag;
		}

		try {
			$qb = $this->db->getQueryBuilder();
			$qb->delete(self::RELATION_TABLE)
				->where($qb->expr()->andX(
					$qb->expr()->eq('objid', $qb->createNamedParameter($objid)),
					$qb->expr()->eq('categoryid', $qb->createNamedParameter($tagId)),
					$qb->expr()->eq('type', $qb->createNamedParameter($this->type)),
				))->executeStatement();
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), [
				'app' => 'core',
				'exception' => $e,
			]);
			return false;
		}
		if ($tag === ITags::TAG_FAVORITE) {
			if ($path === null) {
				$node = $this->userFolder->getFirstNodeById($objid);
				if ($node !== null) {
					$path = $node->getPath();
				} else {
					throw new Exception('Failed to unfavorite: node with id ' . $objid . ' not found');
				}
			}

			$this->dispatcher->dispatchTyped(new NodeRemovedFromFavorite($this->userManager->getExistingUser($this->user), $objid, $path));
		}
		return true;
	}

	#[\Override]
	public function delete(array|string|int $names): bool {
		if (!is_array($names)) {
			$names = [(string)$names];
		}

		$names = array_map('trim', array_map('strval', $names));
		array_filter($names);

		$this->logger->debug(__METHOD__ . ', before: ' . print_r($this->tags, true));
		foreach ($names as $name) {
			$id = null;

			if (is_numeric($name)) {
				$key = $this->getTagById($name);
			} else {
				$key = $this->getTagByName($name);
			}
			if ($key !== false) {
				$tag = $this->tags[$key];
				$id = $tag->id;
				unset($this->tags[$key]);
				$this->mapper->delete($tag);
			} else {
				$this->logger->error(__METHOD__ . 'Cannot delete tag ' . $name . ': not found.');
			}
			if ($id !== null) {
				try {
					$qb = $this->db->getQueryBuilder();
					$qb->delete(self::RELATION_TABLE)
						->where($qb->expr()->eq('categoryid', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
						->executeStatement();
				} catch (\Exception $e) {
					$this->logger->error($e->getMessage(), [
						'app' => 'core',
						'exception' => $e,
					]);
					return false;
				}
			}
		}
		return true;
	}

	// case-insensitive array_search
	protected function array_searchi(string $needle, array $haystack, $mem = 'name'): int|false {
		return array_search(strtolower($needle), array_map(
			function ($tag) use ($mem) {
				return strtolower($tag->{$mem});
			}, $haystack),
			true
		);
	}

	/**
	 * Get a tag's ID.
	 *
	 * @param string $name The tag name to look for.
	 * @return int|false The tag's id or false if no matching tag is found.
	 */
	private function getTagId(string $name): int|false {
		$key = $this->array_searchi($name, $this->tags);
		if ($key !== false) {
			return $this->tags[$key]->id ?? -1;
		}
		return false;
	}

	/**
	 * Get a tag by its name.
	 *
	 * @param string $name The tag name.
	 * @return integer|false The tag object's offset within the $this->tags
	 *                       array or false if it doesn't exist.
	 */
	private function getTagByName(string $name): int|false {
		return $this->array_searchi($name, $this->tags);
	}

	/**
	 * Get a tag by its ID.
	 *
	 * @param string $id The tag ID to look for.
	 * @return integer|false The tag object's offset within the $this->tags array or false if it doesn't exist.
	 */
	private function getTagById(string $id): int|false {
		return $this->array_searchi($id, $this->tags, 'id');
	}

	/**
	 * Returns an array mapping a given tag's properties to its values:
	 * ['id' => 0, 'name' = 'Tag', 'owner' = 'User', 'type' => 'tagtype']
	 *
	 * @param Tag $tag The tag that is going to be mapped
	 * @return array{id: ?int, name: string, owner: string, type: string}
	 */
	private function tagMap(Tag $tag): array {
		return [
			'id' => $tag->id,
			'name' => $tag->name,
			'owner' => $tag->owner,
			'type' => $tag->type
		];
	}
}

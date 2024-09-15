<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bernhard Reiter <ockham@raz.or.at>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Tanghus <thomas@tanghus.net>
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
namespace OC;

use OC\Tagging\Tag;
use OC\Tagging\TagMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\ITags;
use OCP\Share_Backend;
use Psr\Log\LoggerInterface;

class Tags implements ITags {
	/**
	 * Used for storing objectid/categoryname pairs while rescanning.
	 */
	private static array $relations = [];
	private string $type;
	private string $user;
	private IDBConnection $db;
	private LoggerInterface $logger;
	private array $tags = [];

	/**
	 * Are we including tags for shared items?
	 */
	private bool $includeShared = false;

	/**
	 * The current user, plus any owners of the items shared with the current
	 * user, if $this->includeShared === true.
	 */
	private array $owners = [];

	/**
	 * The Mapper we are using to communicate our Tag objects to the database.
	 */
	private TagMapper $mapper;

	/**
	 * The sharing backend for objects of $this->type. Required if
	 * $this->includeShared === true to determine ownership of items.
	 */
	private ?Share_Backend $backend = null;

	public const TAG_TABLE = 'vcategory';
	public const RELATION_TABLE = 'vcategory_to_object';

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
	public function __construct(TagMapper $mapper, string $user, string $type, LoggerInterface $logger, IDBConnection $connection, array $defaultTags = []) {
		$this->mapper = $mapper;
		$this->user = $user;
		$this->type = $type;
		$this->owners = [$this->user];
		$this->tags = $this->mapper->loadTags($this->owners, $this->type);
		$this->db = $connection;
		$this->logger = $logger;

		if (count($defaultTags) > 0 && count($this->tags) === 0) {
			$this->addMultiple($defaultTags, true);
		}
	}

	/**
	 * Check if any tags are saved for this type and user.
	 *
	 * @return boolean
	 */
	public function isEmpty(): bool {
		return count($this->tags) === 0;
	}

	/**
	 * Returns an array mapping a given tag's properties to its values:
	 * ['id' => 0, 'name' = 'Tag', 'owner' = 'User', 'type' => 'tagtype']
	 *
	 * @param string $id The ID of the tag that is going to be mapped
	 * @return array|false
	 */
	public function getTag(string $id) {
		$key = $this->getTagById($id);
		if ($key !== false) {
			return $this->tagMap($this->tags[$key]);
		}
		return false;
	}

	/**
	 * Get the tags for a specific user.
	 *
	 * This returns an array with maps containing each tag's properties:
	 * [
	 * 	['id' => 0, 'name' = 'First tag', 'owner' = 'User', 'type' => 'tagtype'],
	 * 	['id' => 1, 'name' = 'Shared tag', 'owner' = 'Other user', 'type' => 'tagtype'],
	 * ]
	 *
	 * @return array<array-key, array{id: int, name: string}>
	 */
	public function getTags(): array {
		if (!count($this->tags)) {
			return [];
		}

		usort($this->tags, function ($a, $b) {
			return strnatcasecmp($a->getName(), $b->getName());
		});
		$tagMap = [];

		foreach ($this->tags as $tag) {
			if ($tag->getName() !== ITags::TAG_FAVORITE) {
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
			function ($tag) use ($user) {
				return $tag->getOwner() === $user;
			}
		);
	}

	/**
	 * Get the list of tags for the given ids.
	 *
	 * @param array $objIds array of object ids
	 * @return array|false of tags id as key to array of tag names
	 * or false if an error occurred
	 */
	public function getTagsForObjects(array $objIds) {
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
				while ($row = $result->fetch()) {
					$objId = (int)$row['objid'];
					if (!isset($entries[$objId])) {
						$entries[$objId] = [];
					}
					$entries[$objId][] = $row['category'];
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

	/**
	 * Get the a list if items tagged with $tag.
	 *
	 * Throws an exception if the tag could not be found.
	 *
	 * @param string $tag Tag id or name.
	 * @return int[]|false An array of object ids or false on error.
	 * @throws \Exception
	 */
	public function getIdsForTag($tag) {
		$tagId = false;
		if (is_numeric($tag)) {
			$tagId = $tag;
		} elseif (is_string($tag)) {
			$tag = trim($tag);
			if ($tag === '') {
				$this->logger->debug(__METHOD__ . ' Cannot use empty tag names', ['app' => 'core']);
				return false;
			}
			$tagId = $this->getTagId($tag);
		}

		if ($tagId === false) {
			$l10n = \OC::$server->getL10N('core');
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

		while ($row = $result->fetch()) {
			$ids[] = (int)$row['objid'];
		}
		$result->closeCursor();

		return $ids;
	}

	/**
	 * Checks whether a tag is saved for the given user,
	 * disregarding the ones shared with him or her.
	 *
	 * @param string $name The tag name to check for.
	 * @param string $user The user whose tags are to be checked.
	 */
	public function userHasTag(string $name, string $user): bool {
		return $this->array_searchi($name, $this->getTagsForUser($user)) !== false;
	}

	/**
	 * Checks whether a tag is saved for or shared with the current user.
	 *
	 * @param string $name The tag name to check for.
	 */
	public function hasTag(string $name): bool {
		return $this->getTagId($name) !== false;
	}

	/**
	 * Add a new tag.
	 *
	 * @param string $name A string with a name of the tag
	 * @return false|int the id of the added tag or false on error.
	 */
	public function add(string $name) {
		$name = trim($name);

		if ($name === '') {
			$this->logger->debug(__METHOD__ . ' Cannot add an empty tag', ['app' => 'core']);
			return false;
		}
		if ($this->userHasTag($name, $this->user)) {
			// TODO use unique db properties instead of an additional check
			$this->logger->debug(__METHOD__ . ' Tag with name already exists', ['app' => 'core']);
			return false;
		}
		try {
			$tag = new Tag($this->user, $this->type, $name);
			$tag = $this->mapper->insert($tag);
			$this->tags[] = $tag;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), [
				'exception' => $e,
				'app' => 'core',
			]);
			return false;
		}
		$this->logger->debug(__METHOD__ . ' Added an tag with ' . $tag->getId(), ['app' => 'core']);
		return $tag->getId();
	}

	/**
	 * Rename tag.
	 *
	 * @param string|integer $from The name or ID of the existing tag
	 * @param string $to The new name of the tag.
	 * @return bool
	 */
	public function rename($from, string $to): bool {
		$from = trim($from);
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

		if ($this->userHasTag($to, $tag->getOwner())) {
			$this->logger->debug(__METHOD__ . 'A tag named' . $to . 'already exists for user' . $tag->getOwner(), ['app' => 'core']);
			return false;
		}

		try {
			$tag->setName($to);
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

	/**
	 * Add a list of new tags.
	 *
	 * @param string|string[] $names A string with a name or an array of strings containing
	 * the name(s) of the tag(s) to add.
	 * @param bool $sync When true, save the tags
	 * @param int|null $id int Optional object id to add to this|these tag(s)
	 * @return bool Returns false on error.
	 */
	public function addMultiple($names, bool $sync = false, ?int $id = null): bool {
		if (!is_array($names)) {
			$names = [$names];
		}
		$names = array_map('trim', $names);
		array_filter($names);

		$newones = [];
		foreach ($names as $name) {
			if (!$this->hasTag($name) && $name !== '') {
				$newones[] = new Tag($this->user, $this->type, $name);
			}
			if (!is_null($id)) {
				// Insert $objectid, $categoryid  pairs if not exist.
				self::$relations[] = ['objid' => $id, 'tag' => $name];
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
				if (!$this->mapper->tagExists($tag)) {
					$this->mapper->insert($tag);
				}
			} catch (\Exception $e) {
				$this->logger->error($e->getMessage(), [
					'exception' => $e,
					'app' => 'core',
				]);
			}
		}

		// reload tags to get the proper ids.
		$this->tags = $this->mapper->loadTags($this->owners, $this->type);
		$this->logger->debug(__METHOD__ . 'tags' . print_r($this->tags, true), ['app' => 'core']);
		// Loop through temporarily cached objectid/tagname pairs
		// and save relations.
		$tags = $this->tags;
		// For some reason this is needed or array_search(i) will return 0..?
		ksort($tags);
		foreach (self::$relations as $relation) {
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
		self::$relations = []; // reset
	}

	/**
	 * Delete tag/object relations from the db
	 *
	 * @param array $ids The ids of the objects
	 * @return boolean Returns false on error.
	 */
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

	/**
	 * Get favorites for an object type
	 *
	 * @return array|false An array of object ids.
	 */
	public function getFavorites() {
		if (!$this->userHasTag(ITags::TAG_FAVORITE, $this->user)) {
			return [];
		}

		try {
			return $this->getIdsForTag(ITags::TAG_FAVORITE);
		} catch (\Exception $e) {
			\OC::$server->getLogger()->logException($e, [
				'message' => __METHOD__,
				'level' => ILogger::ERROR,
				'app' => 'core',
			]);
			return [];
		}
	}

	/**
	 * Add an object to favorites
	 *
	 * @param int $objid The id of the object
	 * @return boolean
	 */
	public function addToFavorites($objid) {
		if (!$this->userHasTag(ITags::TAG_FAVORITE, $this->user)) {
			$this->add(ITags::TAG_FAVORITE);
		}
		return $this->tagAs($objid, ITags::TAG_FAVORITE);
	}

	/**
	 * Remove an object from favorites
	 *
	 * @param int $objid The id of the object
	 * @return boolean
	 */
	public function removeFromFavorites($objid) {
		return $this->unTag($objid, ITags::TAG_FAVORITE);
	}

	/**
	 * Creates a tag/object relation.
	 *
	 * @param int $objid The id of the object
	 * @param string $tag The id or name of the tag
	 * @return boolean Returns false on error.
	 */
	public function tagAs($objid, $tag) {
		if (is_string($tag) && !is_numeric($tag)) {
			$tag = trim($tag);
			if ($tag === '') {
				$this->logger->debug(__METHOD__.', Cannot add an empty tag');
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
			\OC::$server->getLogger()->error($e->getMessage(), [
				'app' => 'core',
				'exception' => $e,
			]);
			return false;
		}
		return true;
	}

	/**
	 * Delete single tag/object relation from the db
	 *
	 * @param int $objid The id of the object
	 * @param string $tag The id or name of the tag
	 * @return boolean
	 */
	public function unTag($objid, $tag) {
		if (is_string($tag) && !is_numeric($tag)) {
			$tag = trim($tag);
			if ($tag === '') {
				$this->logger->debug(__METHOD__.', Tag name is empty');
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
		return true;
	}

	/**
	 * Delete tags from the database.
	 *
	 * @param string[]|integer[] $names An array of tags (names or IDs) to delete
	 * @return bool Returns false on error
	 */
	public function delete($names) {
		if (!is_array($names)) {
			$names = [$names];
		}

		$names = array_map('trim', $names);
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
				$id = $tag->getId();
				unset($this->tags[$key]);
				$this->mapper->delete($tag);
			} else {
				$this->logger->error(__METHOD__ . 'Cannot delete tag ' . $name . ': not found.');
			}
			if (!is_null($id) && $id !== false) {
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
	protected function array_searchi($needle, $haystack, $mem = 'getName') {
		if (!is_array($haystack)) {
			return false;
		}
		return array_search(strtolower($needle), array_map(
			function ($tag) use ($mem) {
				return strtolower(call_user_func([$tag, $mem]));
			}, $haystack)
		);
	}

	/**
	 * Get a tag's ID.
	 *
	 * @param string $name The tag name to look for.
	 * @return string|bool The tag's id or false if no matching tag is found.
	 */
	private function getTagId($name) {
		$key = $this->array_searchi($name, $this->tags);
		if ($key !== false) {
			return $this->tags[$key]->getId();
		}
		return false;
	}

	/**
	 * Get a tag by its name.
	 *
	 * @param string $name The tag name.
	 * @return integer|bool The tag object's offset within the $this->tags
	 *                      array or false if it doesn't exist.
	 */
	private function getTagByName($name) {
		return $this->array_searchi($name, $this->tags, 'getName');
	}

	/**
	 * Get a tag by its ID.
	 *
	 * @param string $id The tag ID to look for.
	 * @return integer|bool The tag object's offset within the $this->tags
	 *                      array or false if it doesn't exist.
	 */
	private function getTagById($id) {
		return $this->array_searchi($id, $this->tags, 'getId');
	}

	/**
	 * Returns an array mapping a given tag's properties to its values:
	 * ['id' => 0, 'name' = 'Tag', 'owner' = 'User', 'type' => 'tagtype']
	 *
	 * @param Tag $tag The tag that is going to be mapped
	 * @return array
	 */
	private function tagMap(Tag $tag) {
		return [
			'id' => $tag->getId(),
			'name' => $tag->getName(),
			'owner' => $tag->getOwner(),
			'type' => $tag->getType()
		];
	}
}

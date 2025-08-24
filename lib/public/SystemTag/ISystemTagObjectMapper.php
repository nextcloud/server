<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\SystemTag;

/**
 * Public interface to access and manage system-wide tags.
 *
 * @since 9.0.0
 */
interface ISystemTagObjectMapper {
	/**
	 * Get a list of tag ids for the given object ids.
	 *
	 * This returns an array that maps object id to tag ids
	 * [
	 *   1 => array('id1', 'id2'),
	 *   2 => array('id3', 'id2'),
	 *   3 => array('id5'),
	 *   4 => array()
	 * ]
	 *
	 * Untagged objects will have an empty array associated.
	 *
	 * @param string|array $objIds object ids
	 * @param string $objectType object type
	 *
	 * @return array with object id as key and an array
	 *               of tag ids as value
	 *
	 * @since 9.0.0
	 */
	public function getTagIdsForObjects($objIds, string $objectType): array;

	/**
	 * Get a list of objects tagged with $tagIds.
	 *
	 * @param string|array $tagIds Tag id or array of tag ids.
	 * @param string $objectType object type
	 * @param int $limit Count of object ids you want to get
	 * @param string $offset The last object id you already received
	 *
	 * @return string[] array of object ids or empty array if none found
	 *
	 * @throws TagNotFoundException if at least one of the
	 *                              given tags does not exist
	 * @throws \InvalidArgumentException When a limit is specified together with
	 *                                   multiple tag ids
	 *
	 * @since 9.0.0
	 */
	public function getObjectIdsForTags($tagIds, string $objectType, int $limit = 0, string $offset = ''): array;

	/**
	 * Assign the given tags to the given object.
	 *
	 * If at least one of the given tag ids doesn't exist, none of the tags
	 * will be assigned.
	 *
	 * If the relationship already existed, fail silently.
	 *
	 * @param string $objId object id
	 * @param string $objectType object type
	 * @param string|array $tagIds tag id or array of tag ids to assign
	 *
	 * @throws TagNotFoundException if at least one of the
	 *                              given tags does not exist
	 *
	 * @since 9.0.0
	 */
	public function assignTags(string $objId, string $objectType, $tagIds);

	/**
	 * Unassign the given tags from the given object.
	 *
	 * If at least one of the given tag ids doesn't exist, none of the tags
	 * will be unassigned.
	 *
	 * If the relationship did not exist in the first place, fail silently.
	 *
	 * @param string $objId object id
	 * @param string $objectType object type
	 * @param string|array $tagIds tag id or array of tag ids to unassign
	 *
	 * @throws TagNotFoundException if at least one of the
	 *                              given tags does not exist
	 *
	 * @since 9.0.0
	 */
	public function unassignTags(string $objId, string $objectType, $tagIds);

	/**
	 * Checks whether the given objects have the given tag.
	 *
	 * @param string|array $objIds object ids
	 * @param string $objectType object type
	 * @param string $tagId tag id to check
	 * @param bool $all true to check that ALL objects have the tag assigned,
	 *                  false to check that at least ONE object has the tag.
	 *
	 * @return bool true if the condition set by $all is matched, false
	 *              otherwise
	 *
	 * @throws TagNotFoundException if the tag does not exist
	 *
	 * @since 9.0.0
	 */
	public function haveTag($objIds, string $objectType, string $tagId, bool $all = true): bool;


	/**
	 * Get the list of object types that have objects assigned to them.
	 *
	 * @return string[] list of object types
	 *
	 * @since 31.0.0
	 */
	public function getAvailableObjectTypes(): array;

	/**
	 * Set the list of object ids for the given tag.
	 * This will replace the current list of object ids.
	 *
	 * @param string $tagId tag id
	 * @param string $objectType object type
	 * @param string[] $objectIds list of object ids
	 *
	 * @throws TagNotFoundException if the tag does not exist
	 * @since 31.0.0
	 */
	public function setObjectIdsForTag(string $tagId, string $objectType, array $objectIds): void;
}

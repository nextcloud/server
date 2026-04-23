<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Group;

/**
 * Internal interface for group backends that support nested groups
 * (groups whose members are themselves groups).
 *
 * Only OC\Group\Database implements this. External backends (LDAP, SAML, …)
 * are unaware of nesting; membership composition happens in OC\Group\Manager.
 */
interface INestedGroupBackend {
	/**
	 * Add $childGid as a direct subgroup of $parentGid.
	 *
	 * Implementations MUST reject edges that would introduce a cycle
	 * (including the self-edge parent === child).
	 *
	 * @return bool true if the edge was inserted, false if it already existed
	 * @throws \InvalidArgumentException if the edge would create a cycle
	 */
	public function addGroupToGroup(string $childGid, string $parentGid): bool;

	/**
	 * Remove the direct edge parent -> child, if present.
	 *
	 * @return bool true if an edge was removed
	 */
	public function removeGroupFromGroup(string $childGid, string $parentGid): bool;

	/**
	 * Direct child group ids of $parentGid (one level deep).
	 *
	 * @return string[]
	 */
	public function getChildGroups(string $parentGid): array;

	/**
	 * Direct child group ids for multiple parents in a single query.
	 *
	 * Used by the Manager during BFS traversal to avoid one round-trip per node.
	 *
	 * @param list<string> $parentGids
	 * @return array<string, list<string>> map parent_gid -> list of child gids
	 */
	public function getChildGroupsBatch(array $parentGids): array;

	/**
	 * Direct parent group ids of $childGid (one level deep).
	 *
	 * @return string[]
	 */
	public function getParentGroups(string $childGid): array;

	/**
	 * Direct parent group ids for multiple children in a single query.
	 *
	 * @param list<string> $childGids
	 * @return array<string, list<string>> map child_gid -> list of parent gids
	 */
	public function getParentGroupsBatch(array $childGids): array;

	/**
	 * Whether $childGid is a direct child of $parentGid.
	 */
	public function groupInGroup(string $childGid, string $parentGid): bool;
}

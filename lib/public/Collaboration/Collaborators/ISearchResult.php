<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Collaboration\Collaborators;

/**
 * Interface ISearchResult
 *
 * @since 13.0.0
 */
interface ISearchResult {
	/**
	 * @param SearchResultType $type
	 * @param array $matches
	 * @param array|null $exactMatches
	 * @since 13.0.0
	 */
	public function addResultSet(SearchResultType $type, array $matches, ?array $exactMatches = null);

	/**
	 * @param SearchResultType $type
	 * @param string $collaboratorId
	 * @return bool
	 * @since 13.0.0
	 */
	public function hasResult(SearchResultType $type, $collaboratorId);

	/**
	 * Removes all result where $collaborationId exactly matches shareWith of
	 * any of wide and exact result matches of the given type.
	 *
	 * @since 22.0.0
	 */
	public function removeCollaboratorResult(SearchResultType $type, string $collaboratorId): bool;

	/**
	 * @param SearchResultType $type
	 * @since 13.0.0
	 */
	public function unsetResult(SearchResultType $type);

	/**
	 * @param SearchResultType $type
	 * @since 13.0.0
	 */
	public function markExactIdMatch(SearchResultType $type);

	/**
	 * @param SearchResultType $type
	 * @return bool
	 * @since 13.0.0
	 */
	public function hasExactIdMatch(SearchResultType $type);

	/**
	 * @return array
	 * @since 13.0.0
	 */
	public function asArray();
}

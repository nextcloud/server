<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Search;

use NCU\Search\Exceptions\AccountUnavailableException;
use NCU\Search\Exceptions\SearchTruncatedException;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Registered via {@see IRegistrationContext::registerAccountScopedSearchProvider}.
 *
 * @experimental 36.0.0
 */
interface IAccountScopedSearchProvider {
	/**
	 * A stable identifier for this provider, e.g. `files`.
	 *
	 * @return non-empty-lowercase-string
	 */
	public function getId(): string;

	/**
	 * A human-readable name for this provider.
	 *
	 * @return non-empty-string
	 */
	public function getName(): string;

	/**
	 * The properties a caller may build a search query against and the metadata attached to each
	 * result.
	 *
	 * @return list<SearchPropertyDefinition>
	 */
	public function getProperties(): array;

	/**
	 * Search one account's data.
	 *
	 * The results are yielded in an order chosen by the provider that is total and stable, so a
	 * caller can page through them with the offset and limit.
	 *
	 * @param non-empty-string $userId
	 * @param ISearchOperator|null $filter Comparisons on the searchable properties, or null for everything
	 * @return \Generator<AccountScopedSearchResult> Each result contains the selectable properties
	 *                                               from {@see getProperties()} that are not detail-only
	 * @throws AccountUnavailableException when the account's data cannot be read at all
	 * @throws SearchTruncatedException when the search cannot be answered exhaustively
	 */
	public function search(
		string $userId,
		?ISearchOperator $filter,
		int $limit,
		int $offset = 0,
	): \Generator;

	/**
	 * The current state of one item, as seen by one account.
	 *
	 * @param non-empty-string $userId
	 * @param string $id An id previously returned by {@see search()}
	 * @return AccountScopedSearchResult|null The item with every selectable property from
	 *                                        {@see getProperties()} in its metadata, or null when it
	 *                                        no longer exists or is not visible to the account
	 * @throws AccountUnavailableException when the account's data cannot be read at all
	 */
	public function get(string $userId, string $id): ?AccountScopedSearchResult;

	/**
	 * The content of one item, as seen by one account.
	 *
	 * May be evidence under legal hold: never call the returned file's mutating methods.
	 *
	 * @param non-empty-string $userId
	 * @param string $id An id previously returned by {@see search()}
	 * @return ISimpleFile|null null when the item no longer exists or is not visible to the account
	 * @throws AccountUnavailableException when the account's data cannot be read at all
	 */
	public function readContent(string $userId, string $id): ?ISimpleFile;
}

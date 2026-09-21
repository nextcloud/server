<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Search;

use OCP\Files\Search\ISearchQuery;
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
	 * The properties a caller may build a search query against and the metadata `search()`
	 * attaches to each result.
	 *
	 * @return list<SearchPropertyDefinition>
	 */
	public function getProperties(): array;

	/**
	 * Search one account's data with the given query.
	 *
	 * @param non-empty-string $userId
	 * @return \Generator<AccountScopedSearchResult> Each result contains the selectable properties from {@see IAccountScopedSearchProvider::getProperties()} in its metadata
	 */
	public function search(
		string $userId,
		ISearchQuery $query,
	): \Generator;

	/** May be evidence under legal hold: never call the returned file's mutating methods. */
	public function readContent(string $userId, AccountScopedSearchResult $entry): ?ISimpleFile;
}

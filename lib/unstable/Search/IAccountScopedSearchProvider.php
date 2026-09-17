<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Search;

use OCP\Files\Search\ISearchQuery;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Settings\IDeclarativeSettingsForm;

/**
 * Registered via {@see IRegistrationContext::registerAccountScopedSearchProvider}.
 *
 * @psalm-import-type DeclarativeSettingsFormField from IDeclarativeSettingsForm
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
	 * The fields a caller may build a search query against.
	 *
	 * @return list<DeclarativeSettingsFormField>
	 */
	public function getSearchCriterion(): array;

	/**
	 * Every metadata key `search()` may attach to a result, with its display label.
	 *
	 * @return array<non-empty-string, string> Key => UI display string
	 */
	public function getSearchResultMetadataKeys(): array;

	/**
	 * Search one account's data with the given query.
	 *
	 * @param non-empty-string $userId
	 * @return \Generator<AccountScopedSearchResult> Each result contains the fields defined in {@see IAccountScopedSearchProvider::getSearchResultMetadataKeys()} in its metadata
	 */
	public function search(
		string $userId,
		ISearchQuery $query,
	): \Generator;

	/** May be evidence under legal hold: never call the returned file's mutating methods. */
	public function readContent(string $userId, AccountScopedSearchResult $entry): ?ISimpleFile;
}

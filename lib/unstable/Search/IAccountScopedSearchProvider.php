<?php

namespace NCU\Search;

use OCP\Files\Search\ISearchQuery;
use OCP\Search\SearchResultEntry;
use OCP\Settings\IDeclarativeSettingsForm;

/**
 * @psalm-import-type DeclarativeSettingsFormField from IDeclarativeSettingsForm
 */
interface IAccountScopedSearchProvider {
	/**
	 * @return non-empty-lowercase-string
	 */
	public function getId(): string;

	/**
	 * @return non-empty-string
	 */
	public function getName(): string;

	/**
	 * @return list<DeclarativeSettingsFormField>
	 */
	public function getSearchCriterion(): array;

	/**
	 * @return array<non-empty-lowercase-string, string> Key => UI display string
	 */
	public function getSearchResultMetadataKeys(): array;

	/**
	 * @param non-empty-string
	 * @return \Generator<SearchResultEntry> Each SearchResultEntry contains the fields defined in {@see IAccountScopedSearchProvider::getFields()} in its metadata
	 */
	public function search(
		string $userId,
		ISearchQuery $query,
	): \Generator;

	public function readContent(SearchResultEntry $entry): ?string;
}

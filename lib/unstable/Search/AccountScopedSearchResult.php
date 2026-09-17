<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Search;

/**
 * One match from an `IAccountScopedSearchProvider`.
 *
 * Deliberately carries none of `OCP\Search\SearchResultEntry`'s display fields (thumbnail, icon,
 * resource link, …) — those exist for a person clicking a result in the unified search UI, which an
 * account-scoped, system-privileged search has no equivalent of. `id` is required rather than
 * optional: it is the whole reason this type exists, not an afterthought.
 */
final class AccountScopedSearchResult {
	/** @var list<MetadataField> */
	private array $metadata = [];

	public function __construct(
		/** Source-native, stable across renames and moves. */
		private readonly string $id,
		private readonly string $title,
	) {
	}

	public function getId(): string {
		return $this->id;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function addMetaData(MetadataField $field): void {
		$this->metadata[] = $field;
	}

	/**
	 * @return list<MetadataField>
	 */
	public function getMetaData(): array {
		return $this->metadata;
	}
}

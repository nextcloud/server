<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Search;

/**
 * A property of the items an {@see IAccountScopedSearchProvider} returns.
 *
 * @experimental 36.0.0
 */
final class SearchPropertyDefinition {
	/**
	 * @param non-empty-string $name The field name used in search comparisons
	 * @param string $title A human-readable label
	 * @param bool $searchable Whether the property can be used in a search query
	 * @param bool $selectable Whether the property is returned in each result's metadata
	 * @param bool $multiValued Whether the property holds a list of values of its type
	 */
	public function __construct(
		private readonly string $name,
		private readonly string $title,
		private readonly SearchPropertyType $type = SearchPropertyType::String,
		private readonly bool $searchable = false,
		private readonly bool $selectable = false,
		private readonly bool $multiValued = false,
	) {
	}

	/**
	 * @return non-empty-string
	 */
	public function getName(): string {
		return $this->name;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function getType(): SearchPropertyType {
		return $this->type;
	}

	public function isSearchable(): bool {
		return $this->searchable;
	}

	public function isSelectable(): bool {
		return $this->selectable;
	}

	public function isMultiValued(): bool {
		return $this->multiValued;
	}
}

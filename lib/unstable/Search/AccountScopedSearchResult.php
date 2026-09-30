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
 * @experimental 36.0.0
 */
final class AccountScopedSearchResult {
	/** @var array<string, mixed> */
	private array $metadata = [];

	/** @var array<string, string> */
	private array $metadataErrors = [];

	/**
	 * @param string $id Source-native, stable across renames and moves
	 * @experimental 36.0.0
	 */
	public function __construct(
		private readonly string $id,
		private readonly string $title,
	) {
	}

	/**
	 * @experimental 36.0.0
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @experimental 36.0.0
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * @experimental 36.0.0
	 */
	public function setMetadata(string $name, mixed $value): void {
		$this->metadata[$name] = $value;
		unset($this->metadataErrors[$name]);
	}

	/**
	 * Record that a property could not be read, and why.
	 *
	 * @experimental 36.0.0
	 */
	public function setMetadataError(string $name, string $reason): void {
		$this->metadataErrors[$name] = $reason;
		unset($this->metadata[$name]);
	}

	/**
	 * @return array<string, mixed>
	 * @experimental 36.0.0
	 */
	public function getMetadata(): array {
		return $this->metadata;
	}

	/**
	 * @return array<string, string> Property name => why it could not be read
	 * @experimental 36.0.0
	 */
	public function getMetadataErrors(): array {
		return $this->metadataErrors;
	}
}

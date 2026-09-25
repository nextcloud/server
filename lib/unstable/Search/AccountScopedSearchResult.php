<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Search;

/**
 * One match from an `IAccountScopedSearchProvider`.
 */
final class AccountScopedSearchResult {
	/** @var array<string, mixed> */
	private array $metadata = [];

	/** @var array<string, string> */
	private array $metadataErrors = [];

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

	public function setMetadata(string $name, mixed $value): void {
		$this->metadata[$name] = $value;
		unset($this->metadataErrors[$name]);
	}

	/**
	 * Record that a property could not be read, and why.
	 */
	public function setMetadataError(string $name, string $reason): void {
		$this->metadataErrors[$name] = $reason;
		unset($this->metadata[$name]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getMetadata(): array {
		return $this->metadata;
	}

	/**
	 * @return array<string, string> Property name => why it could not be read
	 */
	public function getMetadataErrors(): array {
		return $this->metadataErrors;
	}
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\SystemTag;

/**
 * Serialized <nc:system-tag> elements, shared by every SystemTagList of one request.
 */
final class SystemTagFragmentCache {
	/** @var array<string,string> */
	private array $fragments = [];

	public function get(string $key): ?string {
		return $this->fragments[$key] ?? null;
	}

	public function set(string $key, string $fragment): void {
		$this->fragments[$key] = $fragment;
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\FullTextSearch\Model;

class UnindexedDocument {
	public function __construct(
		private readonly string $id,
		private readonly int $lastModified
	) {
	}

	public function getId(): string {
		return $this->id;
	}

	public function getLastModified(): int {
		return $this->lastModified;
	}
}

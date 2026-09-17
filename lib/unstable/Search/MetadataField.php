<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Search;

class MetadataField {
	private function __construct(
		private string $name,
		private MetadataFieldStatus $status,
		private mixed $value = null,
		private ?string $reason = null,
	) {
	}

	public static function captured(string $name, mixed $value, ?string $reason = null): self {
		if ($value === null) {
			return new self($name, MetadataFieldStatus::NotCaptured, reason: $reason);
		}
		return new self($name, MetadataFieldStatus::Captured, $value);
	}

	public static function notCaptured(string $name, ?string $reason): self {
		return new self($name, MetadataFieldStatus::NotCaptured, reason: $reason);
	}

	public static function notApplicable(string $name): self {
		return new self($name, MetadataFieldStatus::NotApplicable);
	}

	public function getName(): string {
		return $this->name;
	}

	public function getStatus(): MetadataFieldStatus {
		return $this->status;
	}

	public function getValue(): mixed {
		return $this->value;
	}

	public function getReason(): ?string {
		return $this->reason;
	}
}

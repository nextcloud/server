<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\SystemTag;

use OCP\SystemTag\ISystemTag;

class SystemTag implements ISystemTag {
	public function __construct(
		private string $id,
		private string $name,
		private bool $userVisible,
		private bool $userAssignable,
		private ?string $etag = null,
		private ?string $color = null,
	) {
	}

	public function getId(): string {
		return $this->id;
	}

	public function getName(): string {
		return $this->name;
	}

	public function isUserVisible(): bool {
		return $this->userVisible;
	}

	public function isUserAssignable(): bool {
		return $this->userAssignable;
	}

	public function getAccessLevel(): int {
		if (!$this->userVisible) {
			return self::ACCESS_LEVEL_INVISIBLE;
		}

		if (!$this->userAssignable) {
			return self::ACCESS_LEVEL_RESTRICTED;
		}

		return self::ACCESS_LEVEL_PUBLIC;
	}

	public function getETag(): ?string {
		return $this->etag;
	}

	public function getColor(): ?string {
		return $this->color;
	}
}

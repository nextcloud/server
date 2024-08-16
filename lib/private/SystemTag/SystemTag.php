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
	) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isUserVisible(): bool {
		return $this->userVisible;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isUserAssignable(): bool {
		return $this->userAssignable;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAccessLevel(): int {
		if (!$this->userVisible) {
			return self::ACCESS_LEVEL_INVISIBLE;
		}

		if (!$this->userAssignable) {
			return self::ACCESS_LEVEL_RESTRICTED;
		}

		return self::ACCESS_LEVEL_PUBLIC;
	}
}

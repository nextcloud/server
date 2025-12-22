<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Settings;

use OCP\Settings\IIconSection;
use Override;

class Section implements IIconSection {
	public function __construct(
		private string $id,
		private string $name,
		private int $priority,
		private string $icon = '',
	) {
	}

	#[Override]
	public function getID(): string {
		return $this->id;
	}

	#[Override]
	public function getName(): string {
		return $this->name;
	}

	#[Override]
	public function getPriority(): int {
		return $this->priority;
	}

	#[Override]
	public function getIcon(): string {
		return $this->icon;
	}
}

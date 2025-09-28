<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Sections\Admin;

use OCP\IL10N;
use OCP\Settings\IIconSection;

class Users implements IIconSection {
	public function __construct(
		private IL10N $l,
	) {
	}

	public function getID(): string {
		return 'usersdelegation';
	}

	public function getName(): string {
		return $this->l->t('Users');
	}

	public function getPriority(): int {
		return 55;
	}

	public function getIcon(): string {
		return '';
	}
}

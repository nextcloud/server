<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Sections\Admin;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Office implements IIconSection {

	public function __construct(
		private IL10N $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getIcon(): string {
		return $this->urlGenerator->imagePath('core', 'apps/richdocuments.svg');
	}

	#[\Override]
	public function getID(): string {
		return 'office';
	}

	#[\Override]
	public function getName(): string {
		return $this->l->t('Office');
	}

	#[\Override]
	public function getPriority(): int {
		return 50;
	}
}

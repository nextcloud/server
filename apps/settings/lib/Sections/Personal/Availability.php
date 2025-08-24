<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Sections\Personal;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Availability implements IIconSection {

	public function __construct(
		private IL10N $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('dav', 'schedule.svg');
	}

	public function getID(): string {
		return 'availability';
	}

	public function getName(): string {
		return $this->l->t('Availability');
	}

	public function getPriority(): int {
		return 50;
	}
}

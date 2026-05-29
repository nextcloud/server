<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Sections\Personal;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class PersonalInfo implements IIconSection {

	public function __construct(
		private IL10N $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getIcon() {
		return $this->urlGenerator->imagePath('core', 'actions/user.svg');
	}

	#[\Override]
	public function getID(): string {
		return 'personal-info';
	}

	#[\Override]
	public function getName(): string {
		return $this->l->t('Personal info');
	}

	#[\Override]
	public function getPriority(): int {
		return 0;
	}
}

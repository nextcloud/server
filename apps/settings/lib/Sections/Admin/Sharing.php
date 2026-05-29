<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Sections\Admin;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Sharing implements IIconSection {

	public function __construct(
		private IL10N $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getIcon(): string {
		return $this->urlGenerator->imagePath('core', 'actions/share.svg');
	}

	#[\Override]
	public function getID(): string {
		return 'sharing';
	}

	#[\Override]
	public function getName(): string {
		return $this->l->t('Sharing');
	}

	#[\Override]
	public function getPriority(): int {
		return 5;
	}
}

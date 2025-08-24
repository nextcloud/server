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

class Server implements IIconSection {

	public function __construct(
		private IL10N $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('core', 'actions/settings-dark.svg');
	}

	public function getID(): string {
		return 'server';
	}

	public function getName(): string {
		return $this->l->t('Basic settings');
	}

	public function getPriority(): int {
		return 1;
	}
}

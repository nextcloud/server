<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Sections\Admin;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class ArtificialIntelligence implements IIconSection {

	public function __construct(
		private IL10N $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('settings', 'ai.svg');
	}

	public function getID(): string {
		return 'ai';
	}

	public function getName(): string {
		return $this->l->t('Artificial Intelligence');
	}

	public function getPriority(): int {
		return 40;
	}
}

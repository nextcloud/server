<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Sections\Personal;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class LanguageLocale implements IIconSection {

	public function __construct(
		private IL10N $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getIcon() {
		return $this->urlGenerator->imagePath('core', 'actions/timezone.svg');
	}

	#[\Override]
	public function getID(): string {
		return 'language-locale';
	}

	#[\Override]
	public function getName(): string {
		return $this->l->t('Language & locale');
	}

	#[\Override]
	public function getPriority(): int {
		return 5;
	}
}

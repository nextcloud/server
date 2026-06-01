<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Activity;

use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;

class SecurityFilter implements IFilter {

	public function __construct(
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
	) {
	}

	#[\Override]
	public function allowedApps() {
		return [];
	}

	#[\Override]
	public function filterTypes(array $types) {
		return array_intersect(['security'], $types);
	}

	#[\Override]
	public function getIcon() {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.svg'));
	}

	#[\Override]
	public function getIdentifier() {
		return 'security';
	}

	#[\Override]
	public function getName() {
		return $this->l10n->t('Security');
	}

	#[\Override]
	public function getPriority() {
		return 30;
	}
}

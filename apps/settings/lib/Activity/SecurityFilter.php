<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Activity;

use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;

class SecurityFilter implements IFilter {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IL10N */
	private $l10n;

	public function __construct(IURLGenerator $urlGenerator, IL10N $l10n) {
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
	}

	public function allowedApps() {
		return [];
	}

	public function filterTypes(array $types) {
		return array_intersect(['security'], $types);
	}

	public function getIcon() {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.svg'));
	}

	public function getIdentifier() {
		return 'security';
	}

	public function getName() {
		return $this->l10n->t('Security');
	}

	public function getPriority() {
		return 30;
	}
}

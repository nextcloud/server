<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CardDAV\Activity;

use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;

class Filter implements IFilter {

	public function __construct(
		protected IL10N $l,
		protected IURLGenerator $url,
	) {
	}

	/**
	 * @return string Lowercase a-z and underscore only identifier
	 */
	public function getIdentifier(): string {
		return 'contacts';
	}

	/**
	 * @return string A translated string
	 */
	public function getName(): string {
		return $this->l->t('Contacts');
	}

	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 *             the admin section. The filters are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 */
	public function getPriority(): int {
		return 40;
	}

	/**
	 * @return string Full URL to an icon, empty string when none is given
	 */
	public function getIcon(): string {
		return $this->url->getAbsoluteURL($this->url->imagePath('core', 'places/contacts.svg'));
	}

	/**
	 * @param string[] $types
	 * @return string[] An array of allowed apps from which activities should be displayed
	 */
	public function filterTypes(array $types): array {
		return array_intersect(['contacts'], $types);
	}

	/**
	 * @return string[] An array of allowed apps from which activities should be displayed
	 */
	public function allowedApps(): array {
		return [];
	}
}

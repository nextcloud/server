<?php
/**
 * @copyright Copyright (c) 2021 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\CardDAV\Activity;

use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;

class Filter implements IFilter {

	/** @var IL10N */
	protected $l;

	/** @var IURLGenerator */
	protected $url;

	public function __construct(IL10N $l, IURLGenerator $url) {
		$this->l = $l;
		$this->url = $url;
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
	 * the admin section. The filters are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
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

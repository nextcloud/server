<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Activity;

use OCP\Activity\IFilter;
use OCP\Activity\IManager;

class LegacyFilter implements IFilter {

	/** @var IManager */
	protected $manager;
	/** @var string */
	protected $identifier;
	/** @var string */
	protected $name;
	/** @var bool */
	protected $isTopFilter;

	/**
	 * LegacySetting constructor.
	 *
	 * @param IManager $manager
	 * @param string $identifier
	 * @param string $name
	 * @param bool $isTopFilter
	 */
	public function __construct(IManager $manager,
								$identifier,
								$name,
								$isTopFilter) {
		$this->manager = $manager;
		$this->identifier = $identifier;
		$this->name = $name;
		$this->isTopFilter = $isTopFilter;
	}

	/**
	 * @return string Lowercase a-z and underscore only identifier
	 * @since 11.0.0
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @return string A translated string
	 * @since 11.0.0
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 * the admin section. The filters are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 * @since 11.0.0
	 */
	public function getPriority() {
		return $this->isTopFilter ? 40 : 50;
	}

	/**
	 * @return string Full URL to an icon, empty string when none is given
	 * @since 11.0.0
	 */
	public function getIcon() {
		// Old API was CSS class, so we can not use this...
		return '';
	}

	/**
	 * @param string[] $types
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function filterTypes(array $types) {
		return $this->manager->filterNotificationTypes($types, $this->getIdentifier());
	}

	/**
	 * @return string[] An array of allowed apps from which activities should be displayed
	 * @since 11.0.0
	 */
	public function allowedApps() {
		return [];
	}
}


<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Joas Schilling <coding@schilljs.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Authentication\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 20.0.0
 */
class AlternativeLoginOptionsEvent extends Event {

	/** @var array */
	private $loginOptions;

	/**
	 * @since 20.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->loginOptions = [];
	}

	/**
	 * Register a new alternative login option
	 *
	 * @param string $name
	 * @param string $link
	 * @param string $styleClass
	 * @since 20.0.0
	 */
	public function addLoginOption(string $name, string $link, string $styleClass = ''): void {
		$this->loginOptions[] = [
			'name' => $name,
			'href' => $link,
			'style' => $styleClass,
		];
	}

	/**
	 * Get all registered login options
	 *
	 * @return array[]
	 * @since 20.0.0
	 */
	public function getAlternativeLogins(): array {
		return $this->loginOptions;
	}
}

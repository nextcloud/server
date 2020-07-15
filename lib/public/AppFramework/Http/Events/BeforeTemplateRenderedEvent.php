<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OCP\AppFramework\Http\Events;

use OCP\EventDispatcher\Event;

/**
 * Emitted before the rendering step of each TemplateResponse. The event holds a
 * flag that specifies if an user is logged in.
 *
 * @since 20.0.0
 */
class BeforeTemplateRenderedEvent extends Event {
	/** @var bool */
	private $loggedIn;

	/**
	 * @since 20.0.0
	 */
	public function __construct(bool $loggedIn) {
		parent::__construct();

		$this->loggedIn = $loggedIn;
	}

	/**
	 * @since 20.0.0
	 */
	public function isLoggedIn(): bool {
		return $this->loggedIn;
	}
}

<?php
/**
 * @copyright 2017, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\AppFramework\Middleware\Share\Exceptions;

use OCP\AppFramework\Http;

class AuthenticationRequiredException extends \Exception {

	/** @var string */
	private $route;

	/**
	 * AuthenticationRequiredException constructor.
	 *
	 * @param string $route
	 */
	public function __construct($route) {
		$this->route = $route;

		parent::__construct('Public share need authentication', Http::STATUS_FORBIDDEN);
	}

	/**
	 * @return string
	 */
	public function getRoute() {
		return $this->route;
	}
}

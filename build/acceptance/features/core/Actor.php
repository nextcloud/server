<?php

/**
 *
 * @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
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

/**
 * An actor in a test scenario.
 *
 * Every Actor object is intended to be used only in a single test scenario.
 * An Actor can control its web browser thanks to the Mink Session received when
 * it was created, so in each scenario each Actor must have its own Mink
 * Session; the same Mink Session can be used by different Actors in different
 * scenarios, but never by different Actors in the same scenario.
 *
 * The test servers used in an scenario can change between different test runs,
 * so an Actor stores the base URL for the current test server being used; in
 * most cases the tests are specified using relative paths that can be converted
 * to the appropriate absolute URL using locatePath() in the step
 * implementation.
 */
class Actor {

	/**
	 * @var \Behat\Mink\Session
	 */
	private $session;

	/**
	 * @var string
	 */
	private $baseUrl;

	/**
	 * Creates a new Actor.
	 *
	 * @param \Behat\Mink\Session $session the Mink Session used to control its
	 *        web browser.
	 * @param string $baseUrl the base URL used when solving relative URLs.
	 */
	public function __construct(\Behat\Mink\Session $session, $baseUrl) {
		$this->session = $session;
		$this->baseUrl = $baseUrl;
	}

	/**
	 * Sets the base URL.
	 *
	 * @param string $baseUrl the base URL used when solving relative URLs.
	 */
	public function setBaseUrl($baseUrl) {
		$this->baseUrl = $baseUrl;
	}

	/**
	 * Returns the Mink Session used to control its web browser.
	 *
	 * @return \Behat\Mink\Session the Mink Session used to control its web
	 *         browser.
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns the full path for the given relative path based on the base URL.
	 *
	 * @param string relativePath the relative path.
	 * @return string the full path.
	 */
	public function locatePath($relativePath) {
		return $this->baseUrl . $relativePath;
	}

}

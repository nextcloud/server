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
 *
 * An Actor can find elements in its Mink Session using its find() method; it is
 * a wrapper over the find() method provided by Mink that extends it with
 * several features: the element can be looked for based on a Locator object, an
 * exception is thrown if the element is not found, and, optionally, it is
 * possible to try again to find the element several times before giving up.
 *
 * The returned object is also a wrapper over the element itself that
 * automatically handles common causes of failed commands, like clicking on a
 * hidden element; in this case, the wrapper would wait for the element to be
 * visible up to the timeout set to find the element.
 *
 * The amount of time to wait before giving up is specified in each call to
 * find(). However, a general multiplier to be applied to every timeout can be
 * set using setFindTimeoutMultiplier(); this makes possible to retry longer
 * before giving up without modifying the tests themselves. Note that the
 * multiplier affects the timeout, but not the timeout step; the rate at which
 * find() will try again to find the element does not change.
 *
 * All actors share a notebook in which data can be annotated. This makes
 * possible to share data between different test steps, no matter which Actor
 * performs them.
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
	 * @var float
	 */
	private $findTimeoutMultiplier;

	/**
	 * @var array
	 */
	private $sharedNotebook;

	/**
	 * Creates a new Actor.
	 *
	 * @param \Behat\Mink\Session $session the Mink Session used to control its
	 *        web browser.
	 * @param string $baseUrl the base URL used when solving relative URLs.
	 * @param array $sharedNotebook the notebook shared between all actors.
	 */
	public function __construct(\Behat\Mink\Session $session, $baseUrl, &$sharedNotebook) {
		$this->session = $session;
		$this->baseUrl = $baseUrl;
		$this->sharedNotebook = &$sharedNotebook;
		$this->findTimeoutMultiplier = 1;
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
	 * Returns the multiplier for find timeouts.
	 *
	 * @return float the multiplier to apply to find timeouts.
	 */
	public function getFindTimeoutMultiplier() {
		return $this->findTimeoutMultiplier;
	}

	/**
	 * Sets the multiplier for find timeouts.
	 *
	 * @param float $findTimeoutMultiplier the multiplier to apply to find
	 *        timeouts.
	 */
	public function setFindTimeoutMultiplier($findTimeoutMultiplier) {
		$this->findTimeoutMultiplier = $findTimeoutMultiplier;
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

	/**
	 * Finds an element in the Mink Session of this Actor.
	 *
	 * The given element locator is relative to its ancestor (either another
	 * locator or an actual element); if it has no ancestor then the base
	 * document element is used.
	 *
	 * Sometimes an element may not be found simply because it has not appeared
	 * yet; for those cases this method supports trying again to find the
	 * element several times before giving up. The timeout parameter controls
	 * how much time to wait, at most, to find the element; the timeoutStep
	 * parameter controls how much time to wait before trying again to find the
	 * element. If ancestor locators need to be found the timeout is applied
	 * individually to each one, that is, if the timeout is 10 seconds the
	 * method will wait up to 10 seconds to find the ancestor of the ancestor
	 * and, then, up to 10 seconds to find the ancestor and, then, up to 10
	 * seconds to find the element. By default the timeout is 0, so the element
	 * and its ancestor will be looked for just once; the default time to wait
	 * before retrying is half a second. If the timeout is not 0 it will be
	 * affected by the multiplier set using setFindTimeoutMultiplier(), if any.
	 *
	 * When found, the element is returned wrapped in an ElementWrapper; the
	 * ElementWrapper handles common causes of failures when executing commands
	 * in an element, like clicking on a hidden element.
	 *
	 * In any case, if the element, or its ancestors, can not be found a
	 * NoSuchElementException is thrown.
	 *
	 * @param Locator $elementLocator the locator for the element.
	 * @param float $timeout the number of seconds (decimals allowed) to wait at
	 *        most for the element to appear.
	 * @param float $timeoutStep the number of seconds (decimals allowed) to
	 *        wait before trying to find the element again.
	 * @return ElementWrapper an ElementWrapper object for the element.
	 * @throws NoSuchElementException if the element, or its ancestor, can not
	 *         be found.
	 */
	public function find(Locator $elementLocator, $timeout = 0, $timeoutStep = 0.5) {
		$timeout = $timeout * $this->findTimeoutMultiplier;

		$elementFinder = new ElementFinder($this->session, $elementLocator, $timeout, $timeoutStep);

		return new ElementWrapper($elementFinder);
	}

	/**
	 * Returns the shared notebook of the Actors.
	 *
	 * @return array the shared notebook of the Actors.
	 */
	public function &getSharedNotebook() {
		return $this->sharedNotebook;
	}

}

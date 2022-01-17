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
 * Interface for classes that manage a Nextcloud server during acceptance tests.
 *
 * A NextcloudTestServerHelper takes care of setting up a Nextcloud server to be
 * used in acceptance tests through its "setUp" method. It does not matter
 * wheter the server is a fresh new server just started or an already running
 * server; in any case, the state of the server must comply with the initial
 * state expected by the tests (like having performed the Nextcloud installation
 * or having an admin user with certain password).
 *
 * As the IP address and thus its the base URL of the server is not known
 * beforehand, the NextcloudTestServerHelper must provide it through its
 * "getBaseUrl" method. Note that this must be the base URL from the point of
 * view of the Selenium server, which may be a different value than the base URL
 * from the point of view of the acceptance tests themselves.
 *
 * Once the Nextcloud test server is no longer needed the "cleanUp" method will
 * be called; depending on how the Nextcloud test server was set up it may not
 * need to do anything.
 *
 * All the methods throw an exception if they fail to execute; as, due to the
 * current use of this interface, it is just a warning for the test runner and
 * nothing to be explicitly catched a plain base Exception is used.
 */
interface NextcloudTestServerHelper {

	/**
	 * Sets up the Nextcloud test server.
	 *
	 * @throws \Exception if the Nextcloud test server can not be set up.
	 */
	public function setUp();

	/**
	 * Cleans up the Nextcloud test server.
	 *
	 * @throws \Exception if the Nextcloud test server can not be cleaned up.
	 */
	public function cleanUp();

	/**
	 * Returns the base URL of the Nextcloud test server (from the point of view
	 * of the Selenium server).
	 *
	 * @return string the base URL of the Nextcloud test server.
	 * @throws \Exception if the base URL can not be determined.
	 */
	public function getBaseUrl();
}

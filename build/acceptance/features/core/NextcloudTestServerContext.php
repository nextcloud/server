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

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Behat context to run each scenario against a clean Nextcloud server.
 *
 * Before each scenario is run, this context sets up a fresh Nextcloud server
 * with predefined data and configuration. Thanks to this every scenario is
 * independent from the others and they all know the initial state of the
 * server.
 *
 * This context is expected to be used along with RawMinkContext contexts (or
 * subclasses). As the server address can be different for each scenario, this
 * context automatically sets the "base_url" parameter of all its sibling
 * RawMinkContexts; just add NextcloudTestServerContext to the context list of a
 * suite in "behat.yml".
 *
 * The Nextcloud server is set up by running a new Docker container; the Docker
 * image used by the container must provide a Nextcloud server ready to be used
 * by the tests. By default, the image "nextcloud-local-test-acceptance" is
 * used, although that can be customized using the "dockerImageName" parameter
 * in "behat.yml". In the same way, the range of ports in which the Nextcloud
 * server will be published in the local host (by default, "15000-16000") can be
 * customized using the "hostPortRangeForContainer" parameter.
 *
 * Note that using Docker containers as a regular user requires giving access to
 * the Docker daemon to that user. Unfortunately, that makes possible for that
 * user to get root privileges for the system. Please see the
 * NextcloudTestServerDockerHelper documentation for further information on this
 * issue.
 */
class NextcloudTestServerContext implements Context {

	/**
	 * @var NextcloudTestServerDockerHelper
	 */
	private $dockerHelper;

	/**
	 * Creates a new NextcloudTestServerContext.
	 *
	 * @param string $dockerImageName the name of the Docker image that provides
	 *        the Nextcloud test server.
	 * @param string $hostPortRangeForContainer the range of local ports in the
	 *        host in which the port 80 of the container can be published.
	 */
	public function __construct($dockerImageName = "nextcloud-local-test-acceptance", $hostPortRangeForContainer = "15000-16000") {
		$this->dockerHelper = new NextcloudTestServerDockerHelper($dockerImageName, $hostPortRangeForContainer);
	}

	/**
	 * @BeforeScenario
	 *
	 * Sets up the Nextcloud test server before each scenario.
	 *
	 * It starts the Docker container and, once ready, it sets the "base_url"
	 * parameter of the sibling RawMinkContexts to "http://" followed by the IP
	 * address and port of the container; if the Docker container can not be
	 * started after some time an exception is thrown (as it is just a warning
	 * for the test runner and nothing to be explicitly catched a plain base
	 * Exception is used).
	 *
	 * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope the
	 *        BeforeScenario hook scope.
	 * @throws \Exception if the Docker container can not be started.
	 */
	public function setUpNextcloudTestServer(BeforeScenarioScope $scope) {
		$this->dockerHelper->createAndStartContainer();

		$serverAddress = $this->dockerHelper->getNextcloudTestServerAddress();

		$isServerReadyCallback = function() use ($serverAddress) {
			return $this->isServerReady($serverAddress);
		};
		$timeout = 10;
		$timeoutStep = 0.5;
		if (!Utils::waitFor($isServerReadyCallback, $timeout, $timeoutStep)) {
			throw new Exception("Docker container for Nextcloud could not be started");
		}

		$this->setBaseUrlInSiblingRawMinkContexts($scope, "http://" . $serverAddress . "/index.php");
	}

	/**
	 * @AfterScenario
	 *
	 * Cleans up the Nextcloud test server after each scenario.
	 *
	 * It stops and removes the Docker container; if the Docker container can
	 * not be removed after some time an exception is thrown (as it is just a
	 * warning for the test runner and nothing to be explicitly catched a plain
	 * base Exception is used).
	 *
	 * @throws \Exception if the Docker container can not be removed.
	 */
	public function cleanUpNextcloudTestServer() {
		$this->dockerHelper->stopAndRemoveContainer();

		$wasContainerRemovedCallback = function() {
			return !$this->dockerHelper->isContainerRegistered();
		};
		$timeout = 10;
		$timeoutStep = 0.5;
		if (!Utils::waitFor($wasContainerRemovedCallback, $timeout, $timeoutStep)) {
			throw new Exception("Docker container for Nextcloud (" . $this->dockerHelper->getContainerName() . ") could not be removed");
		}
	}

	private function isServerReady($serverAddress) {
		$curlHandle = curl_init("http://" . $serverAddress);

		// Returning the transfer as the result of curl_exec prevents the
		// transfer from being written to the output.
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

		$transfer = curl_exec($curlHandle);

		curl_close($curlHandle);

		return $transfer !== false;
	}

	private function setBaseUrlInSiblingRawMinkContexts(BeforeScenarioScope $scope, $baseUrl) {
		$environment = $scope->getEnvironment();

		foreach ($environment->getContexts() as $context) {
			if ($context instanceof Behat\MinkExtension\Context\RawMinkContext) {
				$context->setMinkParameter("base_url", $baseUrl);
			}
		}
	}

}

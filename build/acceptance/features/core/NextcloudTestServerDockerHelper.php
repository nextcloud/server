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
 * Helper to manage the Docker container for the Nextcloud test server.
 *
 * The NextcloudTestServerDockerHelper provides a Nextcloud test server using a
 * Docker container. The "setUp" method creates and starts the container, while
 * the "cleanUp" method destroys it. A Docker image that provides an installed
 * and ready to run Nextcloud server with the configuration and data expected by
 * the acceptance tests must be available in the system. The Nextcloud server
 * must use a local storage so all the changes it makes are confined to its
 * running container.
 *
 * Also, the Nextcloud server installed in the Docker image is expected to see
 * "127.0.0.1" as a trusted domain (which would be the case if it was installed
 * by running "occ maintenance:install"). Therefore, the Nextcloud server is
 * accessed through a local port in the host system mapped to the port 80 of the
 * Docker container; if the Nextcloud server was instead accessed directly
 * through its IP address it would complain that it was being accessed from an
 * untrusted domain and refuse to work until the admin whitelisted it. The base
 * URL to access the Nextcloud server can be got from "getBaseUrl".
 *
 * Internally, the NextcloudTestServerDockerHelper uses the Docker Command Line
 * Interface (the "docker" command) to run, get information from, and destroy
 * the container, For better compatibility, the used Docker CLI commands follow
 * the pre-1.13 syntax (also available in 1.13 and newer). For example,
 * "docker start" instead of "docker container start".
 *
 * In any case, the "docker" command requires special permissions to talk to the
 * Docker daemon, and those permissions are typically available only to the root
 * user. However, you should NOT run the acceptance tests as root, but as a
 * regular user instead. Please see the Docker documentation to find out how to
 * give access to a regular user to the Docker daemon:
 * https://docs.docker.com/engine/installation/linux/linux-postinstall/
 *
 * Note, however, that being able to communicate with the Docker daemon is the
 * same as being able to get root privileges for the system. Therefore, you must
 * give access to the Docker daemon (and thus run the acceptance tests as) ONLY
 * to trusted and secure users:
 * https://docs.docker.com/engine/security/security/#docker-daemon-attack-surface
 *
 * All the public methods that use the 'docker' command throw an exception if
 * the command can not be executed or if it does not have enough permissions to
 * connect to the Docker daemon; as, due to the current use of this class, it is
 * just a warning for the test runner and nothing to be explicitly catched a
 * plain base Exception is used.
 */
class NextcloudTestServerDockerHelper implements NextcloudTestServerHelper {

	/**
	 * @var string
	 */
	private $imageName;

	/**
	 * @var string
	 */
	private $hostPortRangeForContainer;

	/**
	 * @var string
	 */
	private $containerName;

	/**
	 * Creates a new NextcloudTestServerDockerHelper.
	 *
	 * @param string $imageName the name of the Docker image that provides the
	 *        Nextcloud test server.
	 * @param string $hostPortRangeForContainer the range of local ports in the
	 *        host in which the port 80 of the container can be published.
	 */
	public function __construct($imageName = "nextcloud-local-test-acceptance", $hostPortRangeForContainer = "15000-16000") {
		$this->imageName = $imageName;
		$this->hostPortRangeForContainer = $hostPortRangeForContainer;
		$this->containerName = null;
	}

	/**
	 * Sets up the Nextcloud test server.
	 *
	 * It starts the Docker container and waits for its Nextcloud test server to
	 * be started; if the server does not start after some time an exception is
	 * thrown (as it is just a warning for the test runner and nothing to be
	 * explicitly catched a plain base Exception is used).
	 *
	 * @throws \Exception if the Docker container or its Nextcloud test server
	 *         can not be started.
	 */
	public function setUp() {
		$this->createAndStartContainer();

		$serverAddress = $this->getNextcloudTestServerAddress();

		$isServerReadyCallback = function() use ($serverAddress) {
			return $this->isServerReady($serverAddress);
		};
		$timeout = 10;
		$timeoutStep = 0.5;
		if (!Utils::waitFor($isServerReadyCallback, $timeout, $timeoutStep)) {
			throw new Exception("Docker container for Nextcloud (" . $this->containerName . ") or its Nextcloud test server could not be started");
		}
	}

	/**
	 * Creates and starts the container.
	 *
	 * Note that, even if the container has started, the server it contains may
	 * not have started yet when this method returns.
	 *
	 * @throws \Exception if the Docker command failed to execute.
	 */
	private function createAndStartContainer() {
		$moreEntropy = true;
		$this->containerName = uniqid($this->imageName . "-", $moreEntropy);

		// There is no need to start the web server as root, so it is started
		// directly as www-data instead.
		// The port 80 of the container is mapped to a free port from a range in
		// the host system; due to this it can be accessed from the host using
		// the "127.0.0.1" IP address, which prevents Nextcloud from complaining
		// that it is being accessed from an untrusted domain.
		$this->executeDockerCommand("run --detach --user=www-data --publish 127.0.0.1:" . $this->hostPortRangeForContainer . ":80 --name=" . $this->containerName . " " . $this->imageName);
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

	/**
	 * Cleans up the Nextcloud test server.
	 *
	 * It stops and removes the Docker container; if the Docker container can
	 * not be removed after some time an exception is thrown (as it is just a
	 * warning for the test runner and nothing to be explicitly catched a plain
	 * base Exception is used).
	 *
	 * @throws \Exception if the Docker container can not be removed.
	 */
	public function cleanUp() {
		$this->stopAndRemoveContainer();

		$wasContainerRemovedCallback = function() {
			return !$this->isContainerRegistered();
		};
		$timeout = 10;
		$timeoutStep = 0.5;
		if (!Utils::waitFor($wasContainerRemovedCallback, $timeout, $timeoutStep)) {
			throw new Exception("Docker container for Nextcloud (" . $this->containerName . ") could not be removed");
		}
	}

	/**
	 * Stops and removes the container.
	 *
	 * @throws \Exception if the Docker command failed to execute.
	 */
	private function stopAndRemoveContainer() {
		// Although the Nextcloud image does not define a volume "--volumes" is
		// used anyway just in case any of its ancestor images does.
		$this->executeDockerCommand("rm --volumes --force " . $this->containerName);
	}

	/**
	 * Returns whether the container exists (no matter its state) or not.
	 *
	 * @return boolean true if the container exists, false otherwise.
	 * @throws \Exception if the Docker command failed to execute.
	 */
	private function isContainerRegistered() {
		// With the "--quiet" option "docker ps" only shows the ID of the
		// matching containers, without table headers. Therefore, if the
		// container does not exist the output will be empty (not even a new
		// line, as the last line of output returned by "executeDockerCommand"
		// does not include a trailing new line character).
		return $this->executeDockerCommand("ps --all --quiet --filter 'name=" . $this->containerName . "'") !== "";
	}

	/**
	 * Returns the base URL of the Nextcloud test server.
	 *
	 * @return string the base URL of the Nextcloud test server.
	 * @throws \Exception if the Docker command failed to execute or the
	 *         container is not running.
	 */
	public function getBaseUrl() {
		return "http://" . $this->getNextcloudTestServerAddress() . "/index.php";
	}

	/**
	 * Returns the IP address and port of the Nextcloud test server (which is
	 * mapped to a local port in the host).
	 *
	 * @return string the IP address and port as "$ipAddress:$port".
	 * @throws \Exception if the Docker command failed to execute or the
	 *         container is not running.
	 */
	private function getNextcloudTestServerAddress() {
		return $this->executeDockerCommand("port " . $this->containerName . " 80");
	}

	/**
	 * Executes the given Docker command.
	 *
	 * @return string the last line of output, without trailing new line
	 *         character.
	 * @throws \Exception if the Docker command failed to execute.
	 */
	private function executeDockerCommand($dockerCommand) {
		$output = array();
		$returnValue = 0;
		$lastLine = exec("docker " . $dockerCommand . " 2>&1", $output, $returnValue);

		if ($returnValue !== 0) {
			throw new Exception("Failed to execute 'docker " . $dockerCommand . "': " . implode("\n", $output));
		}

		return $lastLine;
	}

}

<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
require __DIR__ . '/../../vendor/autoload.php';

use Behat\Behat\Context\Exception\ContextNotFoundException;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use PHPUnit\Framework\Assert;

class CommandLineContext implements \Behat\Behat\Context\Context {
	use CommandLine;

	private $lastTransferPath;

	private $featureContext;
	private $localBaseUrl;
	private $remoteBaseUrl;

	public function __construct($ocPath, $baseUrl) {
		$this->ocPath = rtrim($ocPath, '/') . '/';
		$this->localBaseUrl = $baseUrl;
		$this->remoteBaseUrl = $baseUrl;
	}

	/**
	 * @Given Maintenance mode is enabled
	 */
	public function maintenanceModeIsEnabled() {
		$this->runOcc(['maintenance:mode', '--on']);
	}

	/**
	 * @Then Maintenance mode is disabled
	 */
	public function maintenanceModeIsDisabled() {
		$this->runOcc(['maintenance:mode', '--off']);
	}

	/** @BeforeScenario */
	public function gatherContexts(BeforeScenarioScope $scope) {
		$environment = $scope->getEnvironment();
		// this should really be "WebDavContext"
		try {
			$this->featureContext = $environment->getContext('FeatureContext');
		} catch (ContextNotFoundException) {
			$this->featureContext = $environment->getContext('DavFeatureContext');
		}
	}

	private function findLastTransferFolderForUser($sourceUser, $targetUser) {
		$foundPaths = [];
		$results = $this->featureContext->listFolder($targetUser, '', 1);
		foreach ($results as $path => $data) {
			$path = rawurldecode($path);
			$parts = explode(' ', $path);
			if (basename($parts[0]) !== 'Transferred') {
				continue;
			}
			if (isset($parts[2]) && $parts[2] === $sourceUser) {
				// store timestamp as key
				$foundPaths[] = [
					'date' => strtotime(trim($parts[4], '/')),
					'path' => $path,
				];
			}
		}

		if (empty($foundPaths)) {
			return null;
		}

		usort($foundPaths, function ($a, $b) {
			return $a['date'] - $b['date'];
		});

		$davPath = rtrim($this->featureContext->getDavFilesPath($targetUser), '/');

		$foundPath = end($foundPaths)['path'];
		// strip dav path
		return substr($foundPath, strlen($davPath) + 1);
	}

	/**
	 * @When /^transferring ownership from "([^"]+)" to "([^"]+)"$/
	 */
	public function transferringOwnership($user1, $user2) {
		if ($this->runOcc(['files:transfer-ownership', $user1, $user2]) === 0) {
			$this->lastTransferPath = $this->findLastTransferFolderForUser($user1, $user2);
		} else {
			// failure
			$this->lastTransferPath = null;
		}
	}

	/**
	 * @When /^transferring ownership of path "([^"]+)" from "([^"]+)" to "([^"]+)"$/
	 */
	public function transferringOwnershipPath($path, $user1, $user2) {
		$path = '--path=' . $path;
		if ($this->runOcc(['files:transfer-ownership', $path, $user1, $user2]) === 0) {
			$this->lastTransferPath = $this->findLastTransferFolderForUser($user1, $user2);
		} else {
			// failure
			$this->lastTransferPath = null;
		}
	}

	/**
	 * @When /^transferring ownership of path "([^"]+)" from "([^"]+)" to "([^"]+)" with received shares$/
	 */
	public function transferringOwnershipPathWithIncomingShares($path, $user1, $user2) {
		$path = '--path=' . $path;
		if ($this->runOcc(['files:transfer-ownership', $path, $user1, $user2, '--transfer-incoming-shares=1']) === 0) {
			$this->lastTransferPath = $this->findLastTransferFolderForUser($user1, $user2);
		} else {
			// failure
			$this->lastTransferPath = null;
		}
	}

	/**
	 * @When /^using received transfer folder of "([^"]+)" as dav path$/
	 */
	public function usingTransferFolderAsDavPath($user) {
		$davPath = $this->featureContext->getDavFilesPath($user);
		$davPath = rtrim($davPath, '/') . $this->lastTransferPath;
		$this->featureContext->usingDavPath($davPath);
	}

	/**
	 * @Then /^transfer folder name contains "([^"]+)"$/
	 */
	public function transferFolderNameContains($text) {
		Assert::assertStringContainsString($text, $this->lastTransferPath);
	}
}

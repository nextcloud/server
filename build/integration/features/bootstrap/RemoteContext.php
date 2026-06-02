<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;
use OC\Remote\Api\OCS;
use OC\Remote\Credentials;
use OC\Remote\Instance;
use OC\Remote\User;
use OCP\Http\Client\IClientService;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\Server;
use PHPUnit\Framework\Assert;

require __DIR__ . '/autoload.php';

/**
 * Remote context.
 */
class RemoteContext implements Context {
	/** @var Instance */
	protected $remoteInstance;

	/** @var Credentials */
	protected $credentails;

	/** @var User */
	protected $userResult;

	protected $lastException;

	public function __construct(
		private string $remote,
	) {
		require_once __DIR__ . '/../../../../lib/base.php';
	}

	protected function getApiClient() {
		return new OCS($this->remoteInstance, $this->credentails, Server::get(IClientService::class));
	}

	/**
	 * @Given /^using remote server "(REMOTE|NON_EXISTING)"$/
	 *
	 * @param string $remoteServer "NON_EXISTING" or "REMOTE"
	 */
	public function selectRemoteInstance($remoteServer) {
		if ($remoteServer == 'REMOTE') {
			$baseUri = $this->remote;
		} else {
			$baseUri = 'nonexistingnextcloudserver.local';
		}
		$this->lastException = null;
		try {
			$this->remoteInstance = new Instance($baseUri, Server::get(ICacheFactory::class)->createLocal(), Server::get(IClientService::class));
			// trigger the status request
			$this->remoteInstance->getProtocol();
		} catch (\Exception $e) {
			$this->lastException = $e;
		}
	}

	/**
	 * @Then /^the remote version should be "([^"]*)"$/
	 * @param string $version
	 */
	public function theRemoteVersionShouldBe($version) {
		if ($version === '__current_version__') {
			$version = Server::get(IConfig::class)->getSystemValue('version', '0.0.0.0');
		}

		Assert::assertEquals($version, $this->remoteInstance->getVersion());
	}

	/**
	 * @Then /^the remote protocol should be "([^"]*)"$/
	 * @param string $protocol
	 */
	public function theRemoteProtocolShouldBe($protocol) {
		Assert::assertEquals($protocol, $this->remoteInstance->getProtocol());
	}

	/**
	 * @Given /^using credentials "([^"]*)", "([^"]*)"/
	 * @param string $user
	 * @param string $password
	 */
	public function usingCredentials($user, $password) {
		$this->credentails = new Credentials($user, $password);
	}

	/**
	 * @When /^getting the remote user info for "([^"]*)"$/
	 * @param string $user
	 */
	public function remoteUserInfo($user) {
		$this->lastException = null;
		try {
			$this->userResult = $this->getApiClient()->getUser($user);
		} catch (\Exception $e) {
			$this->lastException = $e;
		}
	}

	/**
	 * @Then /^the remote user should have userid "([^"]*)"$/
	 * @param string $user
	 */
	public function remoteUserId($user) {
		Assert::assertEquals($user, $this->userResult->getUserId());
	}

	/**
	 * @Then /^the request should throw a "([^"]*)"$/
	 * @param string $class
	 */
	public function lastError($class) {
		Assert::assertEquals($class, get_class($this->lastException));
	}

	/**
	 * @Then /^the capability "([^"]*)" is "([^"]*)"$/
	 * @param string $key
	 * @param string $value
	 */
	public function hasCapability($key, $value) {
		try {
			$capabilities = $this->getApiClient()->getCapabilities();
		} catch (\Exception $e) {
			Assert::assertInstanceOf($value, $e);
			$this->lastException = $e;
			return;
		}
		$current = $capabilities;
		$parts = explode('.', $key);
		foreach ($parts as $part) {
			if ($current !== null) {
				$current = isset($current[$part]) ? $current[$part] : null;
			}
		}
		Assert::assertEquals($value, $current);
	}
}

<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests\Integration;

use OC\ServerNotAvailableException;
use OCA\User_LDAP\LDAP;

/**
 * Class ExceptionOnLostConnection
 *
 * integration test, ensures that an exception is thrown, when the connection is lost.
 *
 * LDAP must be available via toxiproxy.
 *
 * This test must be run manually.
 *
 */
class ExceptionOnLostConnection {
	/** @var string */
	private $ldapHost;

	/** @var LDAP */
	private $ldap;

	/** @var bool */
	private $originalProxyState;

	/**
	 * @param string $toxiProxyHost host of toxiproxy as url, like http://localhost:8474
	 * @param string $toxiProxyName name of the LDAP proxy service as configured in toxiProxy
	 * @param string $ldapBase any valid LDAP base DN
	 * @param null $ldapBindDN optional, bind DN if anonymous bind is not possible
	 * @param null $ldapBindPwd optional
	 */
	public function __construct(
		private $toxiProxyHost,
		private $toxiProxyName,
		private $ldapBase,
		private $ldapBindDN = null,
		private $ldapBindPwd = null,
	) {
		$this->setUp();
	}

	/**
	 * destructor
	 */
	public function __destruct() {
		$this->cleanUp();
	}

	/**
	 * prepares everything for the test run. Includes loading Nextcloud and
	 * the LDAP backend, as well as getting information about toxiproxy.
	 * Also creates an instance of the LDAP class, the testee
	 *
	 * @throws \Exception
	 */
	public function setUp(): void {
		require_once __DIR__ . '/../../../../lib/base.php';
		\OC_App::loadApps(['user_ldap']);

		$ch = $this->getCurl();
		$proxyInfoJson = curl_exec($ch);
		$this->checkCurlResult($ch, $proxyInfoJson);
		$proxyInfo = json_decode($proxyInfoJson, true);
		$this->originalProxyState = $proxyInfo['enabled'];
		$this->ldapHost = 'ldap://' . $proxyInfo['listen']; // contains port as well

		$this->ldap = new LDAP();
	}

	/**
	 * restores original state of the LDAP proxy, if necessary
	 */
	public function cleanUp() {
		if ($this->originalProxyState === true) {
			$this->setProxyState(true);
		}
	}

	/**
	 * runs the test and prints the result. Exit code is 0 if successful, 1 on
	 * fail
	 */
	public function run() {
		if ($this->originalProxyState === false) {
			$this->setProxyState(true);
		}
		//host contains port, 2nd parameter will be ignored
		$cr = $this->ldap->connect($this->ldapHost, 0);
		$this->ldap->bind($cr, $this->ldapBindDN, $this->ldapBindPwd);
		$this->ldap->search($cr, $this->ldapBase, 'objectClass=*', ['dn'], true, 5);

		// disable LDAP, will cause lost connection
		$this->setProxyState(false);
		try {
			$this->ldap->search($cr, $this->ldapBase, 'objectClass=*', ['dn'], true, 5);
		} catch (ServerNotAvailableException $e) {
			print('Test PASSED' . PHP_EOL);
			exit(0);
		}
		print('Test FAILED' . PHP_EOL);
		exit(1);
	}

	/**
	 * tests whether a curl operation ran successfully. If not, an exception
	 * is thrown
	 *
	 * @param resource|\CurlHandle $ch
	 * @param mixed $result
	 * @throws \Exception
	 */
	private function checkCurlResult($ch, $result) {
		if ($result === false) {
			$error = curl_error($ch);
			curl_close($ch);
			throw new \Exception($error);
		}
	}

	/**
	 * enables or disabled the LDAP proxy service in toxiproxy
	 *
	 * @param bool $isEnabled whether is should be enabled or disables
	 * @throws \Exception
	 */
	private function setProxyState($isEnabled) {
		if (!is_bool($isEnabled)) {
			throw new \InvalidArgumentException('Bool expected');
		}
		$postData = json_encode(['enabled' => $isEnabled]);
		$ch = $this->getCurl();
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen($postData)]
		);
		$recvd = curl_exec($ch);
		$this->checkCurlResult($ch, $recvd);
	}

	/**
	 * initializes a curl handler towards the toxiproxy LDAP proxy service
	 * @return resource|\CurlHandle
	 */
	private function getCurl() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->toxiProxyHost . '/proxies/' . $this->toxiProxyName);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return $ch;
	}
}

$test = new ExceptionOnLostConnection('http://localhost:8474', 'ldap', 'dc=owncloud,dc=bzoc');
$test->run();

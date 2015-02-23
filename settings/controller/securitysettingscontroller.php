<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Settings\Controller;

use \OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IConfig;

/**
 * @package OC\Settings\Controller
 */
class SecuritySettingsController extends Controller {
	/** @var \OCP\IConfig */
	private $config;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 */
	public function __construct($appName,
								IRequest $request,
								IConfig $config) {
		parent::__construct($appName, $request);
		$this->config = $config;
	}

	/**
	 * @return array
	 */
	protected function returnSuccess() {
		return array(
			'status' => 'success'
		);
	}

	/**
	 * @return array
	 */
	protected function returnError() {
		return array(
			'status' => 'error'
		);
	}

	/**
	 * Enforce or disable the enforcement of SSL
	 * @param boolean $enforceHTTPS Whether SSL should be enforced
	 * @return array
	 */
	public function enforceSSL($enforceHTTPS = false) {
		if(!is_bool($enforceHTTPS)) {
			return $this->returnError();
		}
		$this->config->setSystemValue('forcessl', $enforceHTTPS);

		return $this->returnSuccess();
	}

	/**
	 * Enforce or disable the enforcement for SSL on subdomains
	 * @param bool $forceSSLforSubdomains Whether SSL on subdomains should be enforced
	 * @return array
	 */
	public function enforceSSLForSubdomains($forceSSLforSubdomains = false) {
		if(!is_bool($forceSSLforSubdomains)) {
			return $this->returnError();
		}
		$this->config->setSystemValue('forceSSLforSubdomains', $forceSSLforSubdomains);

		return $this->returnSuccess();
	}

	/**
	 * Add a new trusted domain
	 * @param string $newTrustedDomain The newly to add trusted domain
	 * @return array
	 */
	public function trustedDomains($newTrustedDomain) {
		$trustedDomains = $this->config->getSystemValue('trusted_domains');
		$trustedDomains[] = $newTrustedDomain;
		$this->config->setSystemValue('trusted_domains', $trustedDomains);

		return $this->returnSuccess();
	}

}

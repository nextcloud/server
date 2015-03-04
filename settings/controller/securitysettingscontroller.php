<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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

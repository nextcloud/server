<?php
/**
 * @author Noveen Sachdeva "noveen.sachdeva@research.iiit.ac.in"
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
 */

namespace OC\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IUserSession;

/**
 * This controller is responsible for managing white-listed domains for CORS
 *
 * @package OC\Settings\Controller
 */
class CorsController extends Controller {
	/** @var ILogger */
	private $logger;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var string */
	private $userId;

	/** @var IConfig */
	private $config;

	/**
	 * CorsController constructor.
	 *
	 * @param string $AppName The app's name.
	 * @param IRequest $request The request.
	 * @param IUserSession $userSession Logged in user's session
	 * @param ILogger $logger The logger.
	 * @param IURLGenerator $urlGenerator Use for url generation
	 * @param IConfig $config
	 */
	public function __construct($AppName, IRequest $request,
								IUserSession $userSession,
								ILogger $logger,
								IURLGenerator $urlGenerator,
								IConfig $config) {
		parent::__construct($AppName, $request);

		$this->AppName = $AppName;
		$this->config = $config;
		$this->userId = $userSession->getUser()->getUID();
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Returns a redirect response
	 * @return RedirectResponse
	 */
	private function getRedirectResponse() {
		return new RedirectResponse(
			$this->urlGenerator->linkToRouteAbsolute(
				'settings.SettingsPage.getPersonal',
				['sectionid' => 'security']
			) . '#cors'
		);
	}

	/**
	 * Gets all White-listed domains
	 *
	 * @return JSONResponse All the White-listed domains
	 */
	public function getDomains() {
		$userId = $this->userId;

		if (empty($this->config->getUserValue($userId, 'core', 'domains'))) {
			$domains = [];
		} else {
			$domains = json_decode($this->config->getUserValue($userId, 'core', 'domains'));
		}

		return new JSONResponse($domains);
	}

	/**
	 * WhiteLists a domain for CORS
	 *
	 * @param string $domain The domain to whitelist
	 * @return RedirectResponse Redirection to the settings page.
	 */
	public function addDomain($domain) {
		if (!isset($domain) || !self::isValidUrl($domain)) {
			return $this->getRedirectResponse();
		}

		$userId = $this->userId;
		$domains = json_decode($this->config->getUserValue($userId, 'core', 'domains'));
		$domains = array_filter($domains);
		array_push($domains, $domain);

		// In case same domain is added
		$domains = array_unique($domains);

		// Store as comma seperated string
		$domainsString = json_encode($domains);

		$this->config->setUserValue($userId, 'core', 'domains', $domainsString);
		$this->logger->debug("The domain {$domain} has been white-listed.", ['app' => $this->appName]);

		return $this->getRedirectResponse();
	}

	/**
	 * Removes a WhiteListed Domain
	 *
	 * @param string $domain Domain to remove
	 * @return RedirectResponse Redirection to the settings page.
	 */
	public function removeDomain($id) {
		$userId = $this->userId;
		$domains = json_decode($this->config->getUserValue($userId, 'core', 'domains'));

		if ($id >= 0 && $id < count($domains)) {
			unset($domains[$id]);
			$this->config->setUserValue($userId, 'core', 'domains', json_encode($domains));
		}

		return $this->getRedirectResponse();
	}

	/**
	 * Checks whether a URL is valid
	 * @param  string  $url URL to check
	 * @return boolean      whether URL is valid
	 */
	private static function isValidUrl($url) {
		return (filter_var($url, FILTER_VALIDATE_URL) !== false);
	}
}

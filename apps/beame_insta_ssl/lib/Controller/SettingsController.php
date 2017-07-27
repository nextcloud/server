<?php
/**
 * @copyright Copyright (c) 2017 Beame.io LTD <support@beame.io>
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

namespace OCA\BeameInstaSsl\Controller;

use OC\Authentication\Token\DefaultTokenMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;

require_once(__DIR__ . '/../BeameInstaSsl.php');
// use OCA\BeameInstaSsl; // How this should work?

class SettingsController extends Controller {
	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct($appName, IRequest $request, IURLGenerator $urlGenerator, IConfig $config) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
	}

	/**
	 * @param string $name
	 * @return RedirectResponse
	 * @NoCSRFRequired
	 */
	public function getCreds($token) {
		set_time_limit(180); // Usually takes up to 30 seconds
		$t = escapeshellarg($token);
		$out = \OCA\BeameInstaSsl\runCommand("creds getCreds --regToken $t");
		if($out instanceof \OCA\BeameInstaSsl\CommandRunError) {
			$add = 'beame_insta_ssl_error='.urlencode($out->stderr);
		} else {
			error_log("[BeameInstaSsl] got credentials" . json_encode($out));
			$add = 'beame_insta_ssl_got_creds=1';
		}

		return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/index.php/settings/admin/additional?'.$add));
	}

	/**
	 * @param string $fqdn
	 * @return RedirectResponse
	 * @NoCSRFRequired
	 */
	public function start($fqdn) {
		$pid = \OCA\BeameInstaSsl\startTunnel($fqdn);
		return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/index.php/settings/admin/additional'));
	}

	/**
	 * @return RedirectResponse
	 * @NoCSRFRequired
	 */
	public function stop() {
		\OCA\BeameInstaSsl\stopTunnel();
		return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/index.php/settings/admin/additional'));
	}

	/**
	 * @return RedirectResponse
	 * @NoCSRFRequired
	 */
	public function cleanup() {
		$pid = \OCA\BeameInstaSsl\cleanupRunFiles();
		return new RedirectResponse($this->urlGenerator->getAbsoluteURL('/index.php/settings/admin/additional'));
	}

}

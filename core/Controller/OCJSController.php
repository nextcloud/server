<?php
/**
 * @copyright Copyright (c) 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Core\Controller;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\Template\JSConfigHelper;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;

class OCJSController extends Controller {

	/** @var JSConfigHelper */
	private $helper;

	/**
	 * OCJSController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IL10N $l
	 * @param Defaults $defaults
	 * @param IAppManager $appManager
	 * @param ISession $session
	 * @param IUserSession $userSession
	 * @param IConfig $config
	 * @param IGroupManager $groupManager
	 * @param IniGetWrapper $iniWrapper
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct($appName,
								IRequest $request,
								IL10N $l,
								Defaults $defaults,
								IAppManager $appManager,
								ISession $session,
								IUserSession $userSession,
								IConfig $config,
								IGroupManager $groupManager,
								IniGetWrapper $iniWrapper,
								IURLGenerator $urlGenerator) {
		parent::__construct($appName, $request);

		$this->helper = new JSConfigHelper(
			$l,
			$defaults,
			$appManager,
			$session,
			$userSession->getUser(),
			$config,
			$groupManager,
			$iniWrapper,
			$urlGenerator
		);
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @return DataDisplayResponse
	 */
	public function getConfig() {
		$data = $this->helper->getConfig();

		return new DataDisplayResponse($data, Http::STATUS_OK, ['Content-type' => 'text/javascript']);
	}
}

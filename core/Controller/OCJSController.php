<?php
/**
 * @copyright Copyright (c) 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Controller;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\Authentication\Token\IProvider;
use OC\CapabilitiesManager;
use OC\Template\JSConfigHelper;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IInitialStateService;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class OCJSController extends Controller {
	private JSConfigHelper $helper;

	public function __construct(
		string $appName,
		IRequest $request,
		IFactory $l10nFactory,
		Defaults $defaults,
		IAppManager $appManager,
		ISession $session,
		IUserSession $userSession,
		IConfig $config,
		IGroupManager $groupManager,
		IniGetWrapper $iniWrapper,
		IURLGenerator $urlGenerator,
		CapabilitiesManager $capabilitiesManager,
		IInitialStateService $initialStateService,
		IProvider $tokenProvider,
	) {
		parent::__construct($appName, $request);

		$this->helper = new JSConfigHelper(
			$l10nFactory->get('lib'),
			$defaults,
			$appManager,
			$session,
			$userSession->getUser(),
			$config,
			$groupManager,
			$iniWrapper,
			$urlGenerator,
			$capabilitiesManager,
			$initialStateService,
			$tokenProvider
		);
	}

	/**
	 * @NoCSRFRequired
	 * @NoTwoFactorRequired
	 * @PublicPage
	 */
	public function getConfig(): DataDisplayResponse {
		$data = $this->helper->getConfig();

		return new DataDisplayResponse($data, Http::STATUS_OK, ['Content-type' => 'text/javascript']);
	}
}

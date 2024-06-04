<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\CapabilitiesManager;
use OC\Template\JSConfigHelper;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
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
			$initialStateService
		);
	}

	/**
	 * @NoCSRFRequired
	 * @NoTwoFactorRequired
	 * @PublicPage
	 */
	#[FrontpageRoute(verb: 'GET', url: '/core/js/oc.js')]
	public function getConfig(): DataDisplayResponse {
		$data = $this->helper->getConfig();

		return new DataDisplayResponse($data, Http::STATUS_OK, ['Content-type' => 'text/javascript']);
	}
}

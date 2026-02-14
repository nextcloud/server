<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Appstore\Controller;

use OC\App\AppManager;
use OC\App\AppStore\Bundles\BundleFetcher;
use OC\Installer;
use OCA\AppAPI\Service\ExAppsPageService;
use OCA\Appstore\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\Util;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class PageController extends Controller {

	public function __construct(
		IRequest $request,
		private IL10N $l10n,
		private IConfig $config,
		private Installer $installer,
		private AppManager $appManager,
		private IURLGenerator $urlGenerator,
		private IInitialState $initialState,
		private BundleFetcher $bundleFetcher,
		private INavigationManager $navigationManager,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @psalm-suppress UndefinedClass AppAPI is shipped since 30.0.1
	 *
	 * @return TemplateResponse
	 */
	#[NoCSRFRequired]
	#[FrontpageRoute('GET', '/settings/apps', root: '')]
	#[FrontpageRoute('GET', '/settings/apps/{category}', defaults: ['category' => ''], root: '')]
	#[FrontpageRoute('GET', '/settings/apps/{category}/{id}', defaults: ['category' => '', 'id' => ''], root: '')]
	public function viewApps(): TemplateResponse {
		$this->navigationManager->setActiveEntry('core_apps');

		$this->initialState->provideInitialState('appstoreEnabled', $this->config->getSystemValueBool('appstoreenabled', true));
		$this->initialState->provideInitialState('appstoreBundles', $this->getBundles());
		$this->initialState->provideInitialState('appstoreDeveloperDocs', $this->urlGenerator->linkToDocs('developer-manual'));
		$this->initialState->provideInitialState('appstoreUpdateCount', count($this->getAppsWithUpdates()));

		if ($this->appManager->isEnabledForAnyone('app_api')) {
			try {
				Server::get(ExAppsPageService::class)->provideAppApiState($this->initialState);
			} catch (\Psr\Container\NotFoundExceptionInterface|\Psr\Container\ContainerExceptionInterface $e) {
			}
		}

		$policy = new ContentSecurityPolicy();
		$policy->addAllowedImageDomain('https://usercontent.apps.nextcloud.com');

		$templateResponse = new TemplateResponse(Application::APP_ID, 'empty', ['pageTitle' => $this->l10n->t('App store')]);
		$templateResponse->setContentSecurityPolicy($policy);

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'main');

		return $templateResponse;
	}


	private function getAppsWithUpdates() {
		$appClass = new \OC_App();
		$apps = $appClass->listAllApps();
		foreach ($apps as $key => $app) {
			$newVersion = $this->installer->isUpdateAvailable($app['id']);
			if ($newVersion === false) {
				unset($apps[$key]);
			}
		}
		return $apps;
	}

	private function getBundles() {
		$result = [];
		$bundles = $this->bundleFetcher->getBundles();
		foreach ($bundles as $bundle) {
			$result[] = [
				'name' => $bundle->getName(),
				'id' => $bundle->getIdentifier(),
				'appIdentifiers' => $bundle->getAppIdentifiers()
			];
		}
		return $result;
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UpdateNotification\Controller;

use OCA\UpdateNotification\Manager;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\Util;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ChangelogController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private Manager $manager,
		private IAppManager $appManager,
		private IInitialState $initialState,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * This page is only used for clients not support showing the app changelog feature in-app and thus need to show it on a dedicated page.
	 * @param string $app App to show the changelog for
	 * @param string|null $version Version entry to show (defaults to latest installed)
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function showChangelog(string $app, ?string $version = null): TemplateResponse {
		$version = $version ?? $this->appManager->getAppVersion($app);
		$appInfo = $this->appManager->getAppInfo($app) ?? [];
		$appName = $appInfo['name'] ?? $app;

		$changes = $this->manager->getChangelog($app, $version) ?? '';
		// Remove version headline
		/** @var string[] */
		$changes = explode("\n", $changes, 2);
		$changes = trim(end($changes));

		$this->initialState->provideInitialState('changelog', [
			'appName' => $appName,
			'appVersion' => $version,
			'text' => $changes,
		]);

		Util::addScript($this->appName, 'view-changelog-page');
		return new TemplateResponse($this->appName, 'empty');
	}
}

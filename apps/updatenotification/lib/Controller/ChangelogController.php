<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
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
namespace OCA\UpdateNotification\Controller;

use OCA\UpdateNotification\Manager;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;

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
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
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

		\OCP\Util::addScript($this->appName, 'view-changelog-page');
		return new TemplateResponse($this->appName, 'empty');
	}
}

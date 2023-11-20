<?php
/**
 * @copyright Copyright (c) 2018 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OC\CapabilitiesManager;
use OC\Security\IdentityProof\Manager;
use OC\Updater\ChangesCheck;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;

class WhatsNewController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		CapabilitiesManager $capabilitiesManager,
		private IUserSession $userSession,
		IUserManager $userManager,
		Manager $keyManager,
		private IConfig $config,
		private ChangesCheck $whatsNewService,
		private IFactory $langFactory,
		private Defaults $defaults,
	) {
		parent::__construct($appName, $request, $capabilitiesManager, $userSession, $userManager, $keyManager);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get the changes
	 *
	 * @return DataResponse<Http::STATUS_OK, array{changelogURL: string, product: string, version: string, whatsNew?: array{regular: string[], admin: string[]}}, array{}>|DataResponse<Http::STATUS_NO_CONTENT, array<empty>, array{}>
	 *
	 * 200: Changes returned
	 * 204: No changes
	 */
	public function get():DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new \RuntimeException("Acting user cannot be resolved");
		}
		$lastRead = $this->config->getUserValue($user->getUID(), 'core', 'whatsNewLastRead', 0);
		$currentVersion = $this->whatsNewService->normalizeVersion($this->config->getSystemValue('version'));

		if (version_compare($lastRead, $currentVersion, '>=')) {
			return new DataResponse([], Http::STATUS_NO_CONTENT);
		}

		try {
			$iterator = $this->langFactory->getLanguageIterator();
			$whatsNew = $this->whatsNewService->getChangesForVersion($currentVersion);
			$resultData = [
				'changelogURL' => $whatsNew['changelogURL'],
				'product' => $this->defaults->getProductName(),
				'version' => $currentVersion,
			];
			do {
				$lang = $iterator->current();
				if (isset($whatsNew['whatsNew'][$lang])) {
					$resultData['whatsNew'] = $whatsNew['whatsNew'][$lang];
					break;
				}
				$iterator->next();
			} while ($lang !== 'en' && $iterator->valid());
			return new DataResponse($resultData);
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NO_CONTENT);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * Dismiss the changes
	 *
	 * @param string $version Version to dismiss the changes for
	 *
	 * @return DataResponse<Http::STATUS_OK, array<empty>, array{}>
	 * @throws \OCP\PreConditionNotMetException
	 * @throws DoesNotExistException
	 *
	 * 200: Changes dismissed
	 */
	public function dismiss(string $version):DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new \RuntimeException("Acting user cannot be resolved");
		}
		$version = $this->whatsNewService->normalizeVersion($version);
		// checks whether it's a valid version, throws an Exception otherwise
		$this->whatsNewService->getChangesForVersion($version);
		$this->config->setUserValue($user->getUID(), 'core', 'whatsNewLastRead', $version);
		return new DataResponse();
	}
}

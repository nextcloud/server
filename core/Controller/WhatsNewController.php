<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\CapabilitiesManager;
use OC\Security\IdentityProof\Manager;
use OC\Updater\ChangesCheck;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\ServerVersion;

class WhatsNewController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		CapabilitiesManager $capabilitiesManager,
		private IUserSession $userSession,
		IUserManager $userManager,
		Manager $keyManager,
		ServerVersion $serverVersion,
		private IConfig $config,
		private ChangesCheck $whatsNewService,
		private IFactory $langFactory,
		private Defaults $defaults,
	) {
		parent::__construct($appName, $request, $capabilitiesManager, $userSession, $userManager, $keyManager, $serverVersion);
	}

	/**
	 * Get the changes
	 *
	 * @return DataResponse<Http::STATUS_OK, array{changelogURL: string, product: string, version: string, whatsNew?: array{regular: list<string>, admin: list<string>}}, array{}>|DataResponse<Http::STATUS_NO_CONTENT, list<empty>, array{}>
	 *
	 * 200: Changes returned
	 * 204: No changes
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/whatsnew', root: '/core')]
	public function get():DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new \RuntimeException('Acting user cannot be resolved');
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
	 * Dismiss the changes
	 *
	 * @param string $version Version to dismiss the changes for
	 *
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws \OCP\PreConditionNotMetException
	 * @throws DoesNotExistException
	 *
	 * 200: Changes dismissed
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/whatsnew', root: '/core')]
	public function dismiss(string $version):DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new \RuntimeException('Acting user cannot be resolved');
		}
		$version = $this->whatsNewService->normalizeVersion($version);
		// checks whether it's a valid version, throws an Exception otherwise
		$this->whatsNewService->getChangesForVersion($version);
		$this->config->setUserValue($user->getUID(), 'core', 'whatsNewLastRead', $version);
		return new DataResponse();
	}
}

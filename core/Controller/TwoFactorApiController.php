<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OC\Authentication\TwoFactorAuth\ProviderManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\IRequest;
use OCP\IUserManager;

class TwoFactorApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ProviderManager $tfManager,
		private IRegistry $tfRegistry,
		private IUserManager $userManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get two factor authentication provider states
	 *
	 * @param string $user system user id
	 *
	 * @return DataResponse<Http::STATUS_OK, array<string, bool>, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: provider states
	 * 404: user not found
	 */
	#[ApiRoute(verb: 'GET', url: '/state', root: '/twofactor')]
	public function state(string $user): DataResponse {
		$userObject = $this->userManager->get($user);
		if ($userObject !== null) {
			$state = $this->tfRegistry->getProviderStates($userObject);
			return new DataResponse($state);
		}
		return new DataResponse(null, Http::STATUS_NOT_FOUND);
	}

	/**
	 * Enable two factor authentication providers for specific user
	 *
	 * @param string $user system user identifier
	 * @param list<string> $providers collection of TFA provider ids
	 *
	 * @return DataResponse<Http::STATUS_OK, array<string, bool>, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: provider states
	 * 404: user not found
	 */
	#[ApiRoute(verb: 'POST', url: '/enable', root: '/twofactor')]
	public function enable(string $user, array $providers = []): DataResponse {
		$userObject = $this->userManager->get($user);
		if ($userObject !== null) {
			foreach ($providers as $providerId) {
				$this->tfManager->tryEnableProviderFor($providerId, $userObject);
			}
			$state = $this->tfRegistry->getProviderStates($userObject);
			return new DataResponse($state);
		}
		return new DataResponse(null, Http::STATUS_NOT_FOUND);
	}

	/**
	 * Disable two factor authentication providers for specific user
	 *
	 * @param string $user system user identifier
	 * @param list<string> $providers collection of TFA provider ids
	 *
	 * @return DataResponse<Http::STATUS_OK, array<string, bool>, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: provider states
	 * 404: user not found
	 */
	#[ApiRoute(verb: 'POST', url: '/disable', root: '/twofactor')]
	public function disable(string $user, array $providers = []): DataResponse {
		$userObject = $this->userManager->get($user);
		if ($userObject !== null) {
			foreach ($providers as $providerId) {
				$this->tfManager->tryDisableProviderFor($providerId, $userObject);
			}
			$state = $this->tfRegistry->getProviderStates($userObject);
			return new DataResponse($state);
		}
		return new DataResponse(null, Http::STATUS_NOT_FOUND);
	}

}

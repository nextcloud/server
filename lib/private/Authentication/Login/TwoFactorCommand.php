<?php

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

declare(strict_types=1);

namespace OC\Authentication\Login;

use function array_pop;
use function count;
use OC\Authentication\TwoFactorAuth\Manager;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IURLGenerator;

class TwoFactorCommand extends ALoginCommand {

	/** @var Manager */
	private $twoFactorManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(Manager $twoFactorManager,
								IURLGenerator $urlGenerator) {
		$this->twoFactorManager = $twoFactorManager;
		$this->urlGenerator = $urlGenerator;
	}

	public function process(LoginData $loginData): LoginResult {
		if (!$this->twoFactorManager->isTwoFactorAuthenticated($loginData->getUser())) {
			return $this->processNextOrFinishSuccessfully($loginData);
		}

		$this->twoFactorManager->prepareTwoFactorLogin($loginData->getUser(), $loginData->isRememberLogin());

		$providers = $this->twoFactorManager->getProviderSet($loginData->getUser())->getPrimaryProviders();
		if (count($providers) === 1) {
			// Single provider, hence we can redirect to that provider's challenge page directly
			/* @var $provider IProvider */
			$provider = array_pop($providers);
			$url = 'core.TwoFactorChallenge.showChallenge';
			$urlParams = [
				'challengeProviderId' => $provider->getId(),
			];
		} else {
			$url = 'core.TwoFactorChallenge.selectChallenge';
			$urlParams = [];
		}

		if ($loginData->getRedirectUrl() !== null) {
			$urlParams['redirect_url'] = $loginData->getRedirectUrl();
		}

		return LoginResult::success(
			$loginData,
			$this->urlGenerator->linkToRoute($url, $urlParams)
		);
	}

}

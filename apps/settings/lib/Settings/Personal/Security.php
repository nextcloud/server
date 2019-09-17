<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\Settings\Personal;


use function array_filter;
use function array_map;
use function is_null;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\INamedToken;
use OC\Authentication\Token\IProvider as IAuthTokenProvider;
use OC\Authentication\Token\IToken;
use OC\Authentication\TwoFactorAuth\Manager as TwoFactorManager;
use OC\Authentication\TwoFactorAuth\ProviderLoader;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IProvidesPersonalSettings;
use OCP\IInitialStateService;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Session\Exceptions\SessionNotAvailableException;
use OCP\Settings\ISettings;
use OCP\IConfig;

class Security implements ISettings {

	/** @var IInitialStateService */
	private $initialStateService;

	/** @var IUserManager */
	private $userManager;

	/** @var ProviderLoader */
	private $providerLoader;

	/** @var IUserSession */
	private $userSession;

	/** @var string|null */
	private $uid;

	/** @var IConfig */
	private $config;

	public function __construct(IInitialStateService $initialStateService,
								IUserManager $userManager,
								ProviderLoader $providerLoader,
								IUserSession $userSession,
								IConfig $config,
								?string $UserId) {
		$this->initialStateService = $initialStateService;
		$this->userManager = $userManager;
		$this->providerLoader = $providerLoader;
		$this->userSession = $userSession;
		$this->uid = $UserId;
		$this->config = $config;
	}

	public function getForm(): TemplateResponse {
		$user = $this->userManager->get($this->uid);
		$passwordChangeSupported = false;
		if ($user !== null) {
			$passwordChangeSupported = $user->canChangePassword();
		}

		$this->initialStateService->provideInitialState(
			'settings',
			'can_create_app_token',
			$this->userSession->getImpersonatingUserID() === null
		);

		return new TemplateResponse('settings', 'settings/personal/security', [
			'passwordChangeSupported' => $passwordChangeSupported,
			'twoFactorProviderData' => $this->getTwoFactorProviderData(),
			'themedark' => $this->config->getUserValue($this->uid, 'accessibility', 'theme', false)
		]);

	}

	public function getSection(): string {
		return 'security';
	}

	public function getPriority(): int {
		return 10;
	}

	private function getTwoFactorProviderData(): array {
		$user = $this->userSession->getUser();
		if (is_null($user)) {
			// Actually impossible, but still …
			return [];
		}

		return [
			'providers' => array_map(function (IProvidesPersonalSettings $provider) use ($user) {
				return [
					'provider' => $provider,
					'settings' => $provider->getPersonalSettings($user)
				];
			}, array_filter($this->providerLoader->getProviders($user), function (IProvider $provider) {
				return $provider instanceof IProvidesPersonalSettings;
			}))
		];
	}
}

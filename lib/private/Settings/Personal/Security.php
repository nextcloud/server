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

namespace OC\Settings\Personal;


use function array_filter;
use function array_map;
use function is_null;
use OC\Authentication\TwoFactorAuth\Manager as TwoFactorManager;
use OC\Authentication\TwoFactorAuth\ProviderLoader;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IProvidesPersonalSettings;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Settings\ISettings;

class Security implements ISettings {

	/** @var IUserManager */
	private $userManager;

	/** @var TwoFactorManager */
	private $twoFactorManager;

	/** @var ProviderLoader */
	private $providerLoader;

	/** @var IUserSession */
	private $userSession;


	public function __construct(IUserManager $userManager,
								TwoFactorManager $providerManager,
								ProviderLoader $providerLoader,
								IUserSession $userSession) {
		$this->userManager = $userManager;
		$this->twoFactorManager = $providerManager;
		$this->providerLoader = $providerLoader;
		$this->userSession = $userSession;
	}

	/**
	 * @return TemplateResponse returns the instance with all parameters set, ready to be rendered
	 * @since 9.1
	 */
	public function getForm() {
		$user = $this->userManager->get(\OC_User::getUser());
		$passwordChangeSupported = false;
		if ($user !== null) {
			$passwordChangeSupported = $user->canChangePassword();
		}

		return new TemplateResponse('settings', 'settings/personal/security', [
			'passwordChangeSupported' => $passwordChangeSupported,
			'twoFactorProviderData' => $this->getTwoFactorProviderData(),
		]);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 * @since 9.1
	 */
	public function getSection() {
		return 'security';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 * @since 9.1
	 */
	public function getPriority() {
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

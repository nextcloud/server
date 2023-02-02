<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\Settings\Settings\Admin;

use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Encryption\IManager;
use OCP\IUserManager;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class Security implements ISettings {
	private IManager $manager;
	private IUserManager $userManager;
	private MandatoryTwoFactor $mandatoryTwoFactor;
	private IInitialState $initialState;
	private IURLGenerator $urlGenerator;

	public function __construct(IManager $manager,
								IUserManager $userManager,
								MandatoryTwoFactor $mandatoryTwoFactor,
								IInitialState $initialState,
								IURLGenerator $urlGenerator) {
		$this->manager = $manager;
		$this->userManager = $userManager;
		$this->mandatoryTwoFactor = $mandatoryTwoFactor;
		$this->initialState = $initialState;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$encryptionModules = $this->manager->getEncryptionModules();
		$defaultEncryptionModuleId = $this->manager->getDefaultEncryptionModuleId();
		$encryptionModuleList = [];
		foreach ($encryptionModules as $module) {
			$encryptionModuleList[$module['id']]['displayName'] = $module['displayName'];
			$encryptionModuleList[$module['id']]['default'] = false;
			if ($module['id'] === $defaultEncryptionModuleId) {
				$encryptionModuleList[$module['id']]['default'] = true;
			}
		}

		$this->initialState->provideInitialState('mandatory2FAState', $this->mandatoryTwoFactor->getState());
		$this->initialState->provideInitialState('two-factor-admin-doc', $this->urlGenerator->linkToDocs('admin-2fa'));
		$this->initialState->provideInitialState('encryption-enabled', $this->manager->isEnabled());
		$this->initialState->provideInitialState('encryption-ready', $this->manager->isReady());
		$this->initialState->provideInitialState('external-backends-enabled', count($this->userManager->getBackends()) > 1);
		$this->initialState->provideInitialState('encryption-modules', $encryptionModuleList);
		$this->initialState->provideInitialState('encryption-admin-doc', $this->urlGenerator->linkToDocs('admin-encryption'));

		return new TemplateResponse('settings', 'settings/admin/security', [], '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return 'security';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 10;
	}
}

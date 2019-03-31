<?php

declare(strict_types=1);

/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OC\Settings\Controller;

use OC\Authentication\TwoFactorAuth\EnforcementState;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OC\Authentication\TwoFactorAuth\Manager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OC\User\Manager as UserManager;

class TwoFactorSettingsController extends Controller {

	/** @var MandatoryTwoFactor */
	private $mandatoryTwoFactor;

	/** @var Manager */
	private $manager;

	private $userManager;

	public function __construct(string $appName,
								IRequest $request,
								MandatoryTwoFactor $mandatoryTwoFactor,
								Manager $manager,
								UserManager $userManager) {
		parent::__construct($appName, $request);

		$this->mandatoryTwoFactor = $mandatoryTwoFactor;
		$this->manager = $manager;
		$this->userManager = $userManager;
	}

	public function index(): JSONResponse {
		return new JSONResponse($this->mandatoryTwoFactor->getState());
	}

	public function update(bool $enforced, array $enforcedGroups = [], array $excludedGroups = []): JSONResponse {
		$this->mandatoryTwoFactor->setState(
			new EnforcementState($enforced, $enforcedGroups, $excludedGroups)
		);

		return new JSONResponse($this->mandatoryTwoFactor->getState());
	}

	public function getEnabledProvidersForUser($uid): JSONResponse {
		error_log('uid: '.$uid,3,'uid');
		$user = $this->userManager->get($uid);
		return new JSONResponse($this->manager->getProviderSet($user)->getPrimaryProviders());
	}

}

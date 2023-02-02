<?php

declare(strict_types=1);

/**
 * @copyright 2022 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author 2022 Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OCA\Settings\WellKnown;

use OCP\AppFramework\Http\RedirectResponse;
use OCP\Http\WellKnown\GenericResponse;
use OCP\Http\WellKnown\IHandler;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;
use OCP\IURLGenerator;

class ChangePasswordHandler implements IHandler {

	private IURLGenerator $urlGenerator;

	public function __construct(IURLGenerator $urlGenerator) {
		$this->urlGenerator = $urlGenerator;
	}

	public function handle(string $service, IRequestContext $context, ?IResponse $previousResponse): ?IResponse {
		if ($service !== 'change-password') {
			return $previousResponse;
		}

		$response = new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('settings.PersonalSettings.index', ['section' => 'security']));
		return new GenericResponse($response);
	}
}

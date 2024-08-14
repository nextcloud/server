<?php

declare(strict_types=1);

/**
 * @copyright 2021 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author 2021 Lukas Reschke <lukas@statuscode.ch>
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

use OCP\AppFramework\Http\TextPlainResponse;
use OCP\Http\WellKnown\GenericResponse;
use OCP\Http\WellKnown\IHandler;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;

class SecurityTxtHandler implements IHandler {
	public function handle(string $service, IRequestContext $context, ?IResponse $previousResponse): ?IResponse {
		if ($service !== 'security.txt') {
			return $previousResponse;
		}

		$response = "Contact: https://hackerone.com/nextcloud
Expires: 2025-02-28T23:00:00.000Z
Acknowledgments: https://hackerone.com/nextcloud/thanks
Acknowledgments: https://github.com/nextcloud/security-advisories/security/advisories
Policy: https://hackerone.com/nextcloud
Preferred-Languages: en";

		return new GenericResponse(new TextPlainResponse($response, 200));
	}
}

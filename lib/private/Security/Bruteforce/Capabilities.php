<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2017 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
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
namespace OC\Security\Bruteforce;

use OCP\Capabilities\IInitialStateExcludedCapability;
use OCP\Capabilities\IPublicCapability;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;

class Capabilities implements IPublicCapability, IInitialStateExcludedCapability {
	public function __construct(
		private IRequest $request,
		private IThrottler $throttler,
	) {
	}

	/**
	 * @return array{bruteforce: array{delay: int, allow-listed: bool}}
	 */
	public function getCapabilities(): array {
		return [
			'bruteforce' => [
				'delay' => $this->throttler->getDelay($this->request->getRemoteAddress()),
				'allow-listed' => $this->throttler->isBypassListed($this->request->getRemoteAddress()),
			],
		];
	}
}

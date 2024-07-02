<?php

declare(strict_types=1);

/**
 * @copyright 2024 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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

namespace OCA\Settings\WellKnown;

use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\WellKnown\GenericResponse;
use OCP\Http\WellKnown\IHandler;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;
use OCP\IConfig;

class AssetLinksHandler implements IHandler {

	public function __construct(
		private IConfig $config,
	) {
	}

	public function handle(string $service, IRequestContext $context, ?IResponse $previousResponse): ?IResponse {
		if ($service !== 'assetlinks.json') {
			return $previousResponse;
		}

		$data = json_decode(file_get_contents(__DIR__ . '/../../data/assetlinks-template.json'), true);
		$data[0]['target']['package_name'] = $this->config->getSystemValueString('assetlinks_package_name', 'com.nextcloud.client');
		$data[0]['target']['sha256_cert_fingerprints'] = $this->config->getSystemValue(
			'assetlinks_sha256_cert_fingerprints',
			[
				'59:BF:BB:8A:5C:17:53:D6:69:AE:C0:D8:CC:D0:DA:82:76:FE:8E:AC:81:A4:45:22:AE:68:0E:A7:74:81:A3:32',
			],
		);
		return new GenericResponse(new JSONResponse($data));
	}
}

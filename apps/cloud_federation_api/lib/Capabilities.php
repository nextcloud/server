<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
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

namespace OCA\CloudFederationAPI;

use OC\OCM\Model\OCMProvider;
use OC\OCM\Model\OCMResource;
use OCP\Capabilities\ICapability;
use OCP\IURLGenerator;
use OCP\OCM\Exceptions\OCMArgumentException;

class Capabilities implements ICapability {

	public const API_VERSION = '1.0-proposal1';

	public function __construct(
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * Function an app uses to return the capabilities
	 *
	 * @return array{
	 *     ocm: array{
	 *         enabled: bool,
	 *         apiVersion: string,
	 *         endPoint: string,
	 *         resourceTypes: array{
	 *             name: string,
	 *             shareTypes: string[],
	 *             protocols: array<string, string>
	 *           }[],
	 *       },
	 * }
	 * @throws OCMArgumentException
	 */
	public function getCapabilities() {
		$url = $this->urlGenerator->linkToRouteAbsolute('cloud_federation_api.requesthandlercontroller.addShare');
		$provider = new OCMProvider();
		$provider->setEnabled(true);
		$provider->setApiVersion(self::API_VERSION);

		$pos = strrpos($url, '/');
		if (false === $pos) {
			throw new OCMArgumentException('generated route should contains a slash character');
		}

		$provider->setEndPoint(substr($url, 0, $pos));

		$resource = new OCMResource();
		$resource->setName('file')
				 ->setShareTypes(['user', 'group'])
				 ->setProtocols(['webdav' => '/public.php/webdav/']);

		$provider->setResourceTypes([$resource]);

		return ['ocm' => $provider->jsonSerialize()];
	}
}

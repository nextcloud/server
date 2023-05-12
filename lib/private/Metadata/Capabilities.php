<?php

declare(strict_types=1);

/**
 * @copyright Copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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
namespace OC\Metadata;

use OCP\Capabilities\IPublicCapability;
use OCP\IConfig;

class Capabilities implements IPublicCapability {
	private IMetadataManager $manager;
	private IConfig $config;

	public function __construct(IMetadataManager $manager, IConfig $config) {
		$this->manager = $manager;
		$this->config = $config;
	}

	public function getCapabilities() {
		if ($this->config->getSystemValueBool('enable_file_metadata', true)) {
			return ['metadataAvailable' => $this->manager->getCapabilities()];
		}

		return [];
	}
}

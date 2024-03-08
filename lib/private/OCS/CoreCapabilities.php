<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\OCS;

use OCP\Capabilities\ICapability;
use OCP\IConfig;
use OCP\IURLGenerator;

/**
 * Class Capabilities
 *
 * @package OC\OCS
 */
class CoreCapabilities implements ICapability {
	/**
	 * @param IConfig $config
	 */
	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * Return this classes capabilities
	 */
	public function getCapabilities(): array {
		return [
			'core' => [
				'pollinterval' => $this->config->getSystemValue('pollinterval', 60),
				'webdav-root' => $this->config->getSystemValue('webdav-root', 'remote.php/webdav'),
				'reference-api' => true,
				'reference-regex' => IURLGenerator::URL_REGEX_NO_MODIFIERS,
			],
		];
	}
}

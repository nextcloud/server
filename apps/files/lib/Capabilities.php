<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tom Needham <tom@owncloud.com>
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
namespace OCA\Files;

use OCP\Capabilities\ICapability;
use OCP\IConfig;

class Capabilities implements ICapability {

	protected IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array{files: array{bigfilechunking: bool, blacklisted_files: array<mixed>}}
	 */
	public function getCapabilities() {
		return [
			'files' => [
				'bigfilechunking' => true,
				'blacklisted_files' => (array)$this->config->getSystemValue('blacklisted_files', ['.htaccess'])
			],
		];
	}
}

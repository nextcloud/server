<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OCA\Files_Versions;

use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;
use OCP\IConfig;

class Capabilities implements ICapability {
	private IConfig $config;
	private IAppManager $appManager;

	public function __construct(
		IConfig $config,
		IAppManager $appManager
	) {
		$this->config = $config;
		$this->appManager = $appManager;
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array
	 */
	public function getCapabilities() {
		$groupFolderOrS3VersioningInstalled = $this->appManager->isInstalled('groupfolders') || $this->appManager->isInstalled('files_versions_s3');

		return [
			'files' => [
				'versioning' => true,
				'version_labeling' => !$groupFolderOrS3VersioningInstalled && $this->config->getSystemValueBool('enable_version_labeling', true),
				'version_deletion' => !$groupFolderOrS3VersioningInstalled && $this->config->getSystemValueBool('enable_version_deletion', true),
			]
		];
	}
}

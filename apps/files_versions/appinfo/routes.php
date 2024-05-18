<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCA\Files_Versions\AppInfo;

/** @var Application $application */
$application = \OC::$server->query(Application::class);
$application->registerRoutes($this, [
	'routes' => [
		[
			'name' => 'Preview#getPreview',
			'url' => '/preview',
			'verb' => 'GET',
		],
	],
]);

/** @var $this \OCP\Route\IRouter */
$this->create('files_versions_download', 'apps/files_versions/download.php')
	->actionInclude('files_versions/download.php');
$this->create('files_versions_ajax_getVersions', 'apps/files_versions/ajax/getVersions.php')
	->actionInclude('files_versions/ajax/getVersions.php');
$this->create('files_versions_ajax_rollbackVersion', 'apps/files_versions/ajax/rollbackVersion.php')
	->actionInclude('files_versions/ajax/rollbackVersion.php');

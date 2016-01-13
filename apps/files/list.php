<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

// Check if we are a user
OCP\User::checkLoggedIn();

$config = \OC::$server->getConfig();
// TODO: move this to the generated config.js
$publicUploadEnabled = $config->getAppValue('core', 'shareapi_allow_public_upload', 'yes');
$uploadLimit=OCP\Util::uploadLimit();

// renders the controls and table headers template
$tmpl = new OCP\Template('files', 'list', '');
$tmpl->assign('uploadLimit', $uploadLimit); // PHP upload limit
$tmpl->assign('publicUploadEnabled', $publicUploadEnabled);
$tmpl->printPage();


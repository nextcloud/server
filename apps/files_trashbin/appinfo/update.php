<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

$config = \OC::$server->getConfig();
$installedVersion = $config->getAppValue('files_trashbin', 'installed_version');

if (version_compare($installedVersion, '0.6.4', '<')) {
	$isExpirationEnabled = $config->getSystemValue('trashbin_auto_expire', true);
	$oldObligation = $config->getSystemValue('trashbin_retention_obligation', null);

	$newObligation = 'auto';
	if ($isExpirationEnabled) {
		if (!is_null($oldObligation)) {
			$newObligation = strval($oldObligation) . ', auto';
		}
	} else {
		$newObligation = 'disabled';
	}

	$config->setSystemValue('trashbin_retention_obligation', $newObligation);
	$config->deleteSystemValue('trashbin_auto_expire');
}

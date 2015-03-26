<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Florin Peter <github@florin-peter.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
\OCP\Util::addscript('files_encryption', 'encryption');
\OCP\Util::addscript('files_encryption', 'detect-migration');

if (!OC_Config::getValue('maintenance', false)) {
	OC_FileProxy::register(new OCA\Files_Encryption\Proxy());

	// User related hooks
	OCA\Files_Encryption\Helper::registerUserHooks();

	// Sharing related hooks
	OCA\Files_Encryption\Helper::registerShareHooks();

	// Filesystem related hooks
	OCA\Files_Encryption\Helper::registerFilesystemHooks();

	// App manager related hooks
	OCA\Files_Encryption\Helper::registerAppHooks();

	if(!in_array('crypt', stream_get_wrappers())) {
		stream_wrapper_register('crypt', 'OCA\Files_Encryption\Stream');
	}
} else {
	// logout user if we are in maintenance to force re-login
	OCP\User::logout();
}

\OC::$server->getCommandBus()->requireSync('\OC\Command\FileAccess');

// Register settings scripts
OCP\App::registerAdmin('files_encryption', 'settings-admin');
OCP\App::registerPersonal('files_encryption', 'settings-personal');

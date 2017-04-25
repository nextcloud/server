<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$directories = [
	__DIR__ . '/../core/l10n',
	__DIR__ . '/../settings/l10n',
	__DIR__ . '/../apps/admin_audit/l10n',
	__DIR__ . '/../apps/comments/l10n',
	__DIR__ . '/../apps/dav/l10n',
	__DIR__ . '/../apps/encryption/l10n',
	__DIR__ . '/../apps/federatedfilesharing/l10n',
	__DIR__ . '/../apps/federation/l10n',
	__DIR__ . '/../apps/files/l10n',
	__DIR__ . '/../apps/files_external/l10n',
	__DIR__ . '/../apps/files_sharing/l10n',
	__DIR__ . '/../apps/files_trashbin/l10n',
	__DIR__ . '/../apps/files_versions/l10n',
	__DIR__ . '/../apps/lookup_server_connector/l10n',
	__DIR__ . '/../apps/provisioning_api/l10n',
	__DIR__ . '/../apps/sharebymail/l10n',
	__DIR__ . '/../apps/systemtags/l10n',
	__DIR__ . '/../apps/testing/l10n',
	__DIR__ . '/../apps/theming/l10n',
	__DIR__ . '/../apps/twofactor_backupcodes/l10n',
	__DIR__ . '/../apps/updatenotification/l10n',
	__DIR__ . '/../apps/user_ldap/l10n',
	__DIR__ . '/../apps/workflowengine/l10n',
];

$errors = [];
foreach ($directories as $dir) {
	if (!file_exists($dir)) {
		continue;
	}
	$directory = new \DirectoryIterator($dir);

	foreach ($directory as $file) {
		if ($file->getExtension() !== 'json') {
			continue;
		}

		$content = file_get_contents($file->getPathname());
		$json = json_decode($content, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			echo '[Error] Could not parse: ' . $file->getPathname() . "\n";
			echo '  ' . json_last_error_msg() . "\n";
			$errors[] = $file->getPathname() . "\n" . '  ' . json_last_error_msg() . "\n";
		} else {
			echo '[OK]   ' . $file->getPathname() . "\n";
		}
	}
}

echo "\n\n";
if (count($errors) > 0) {
	echo sprintf('ERROR: There were %d errors:', count($errors)) . "\n";
	echo implode("\n", $errors);
	exit(1);
}

echo 'OK: all files parse';
exit(0);

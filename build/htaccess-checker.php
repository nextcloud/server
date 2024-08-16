<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Script to check that the main .htaccess file doesn't include some automated
 * changes done by Nextcloud.
 */

$htaccess = file_get_contents(__DIR__ . '/../.htaccess');
if (strpos($htaccess, 'DO NOT CHANGE ANYTHING') !== false) {
	echo(".htaccess file has invalid changes!\n");
	exit(1);
} else {
	exit(0);
}

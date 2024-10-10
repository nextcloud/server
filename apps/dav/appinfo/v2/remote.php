<?php

use OCA\DAV\Server;

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// no php execution timeout for webdav
if (!str_contains(@ini_get('disable_functions'), 'set_time_limit')) {
	@set_time_limit(0);
}
ignore_user_abort(true);

// Turn off output buffering to prevent memory problems
\OC_Util::obEnd();

$request = \OC::$server->getRequest();
$server = new Server($request, $baseuri);
$server->exec();

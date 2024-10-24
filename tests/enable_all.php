<?php
/**
 * SPDX-FileCopyrightText: 2016-2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2012-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

require_once __DIR__ . '/../lib/base.php';

function enableApp($app) {
	try {
		(new \OC_App())->enable($app);
	} catch (Exception $e) {
		echo $e;
	}
}

enableApp('files_sharing');
enableApp('files_trashbin');
enableApp('encryption');
enableApp('user_ldap');
enableApp('files_versions');
enableApp('provisioning_api');
enableApp('federation');
enableApp('federatedfilesharing');
enableApp('admin_audit');
enableApp('webhook_listeners');

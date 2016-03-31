<?php
/**
 * Copyright (c) 2012 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once __DIR__.'/../lib/base.php';

function enableApp($app) {
	try {
		OC_App::enable($app);
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

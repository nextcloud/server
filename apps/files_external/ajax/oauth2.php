<?php
/**
 * @author Adam Williamson <awilliam@redhat.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Vincent Petry <pvince81@owncloud.com>
 * @author Volkan Gezer <volkangezer@gmail.com>
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
set_include_path(get_include_path().PATH_SEPARATOR.
	\OC_App::getAppPath('files_external').'/3rdparty/google-api-php-client/src');
require_once 'Google/autoload.php';

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
$l = \OC::$server->getL10N('files_external');

// FIXME: currently hard-coded to Google Drive
if (isset($_POST['client_id']) && isset($_POST['client_secret']) && isset($_POST['redirect'])) {
	$client = new Google_Client();
	$client->setClientId((string)$_POST['client_id']);
	$client->setClientSecret((string)$_POST['client_secret']);
	$client->setRedirectUri((string)$_POST['redirect']);
	$client->setScopes(array('https://www.googleapis.com/auth/drive'));
	$client->setApprovalPrompt('force');
	$client->setAccessType('offline');
	if (isset($_POST['step'])) {
		$step = $_POST['step'];
		if ($step == 1) {
			try {
				$authUrl = $client->createAuthUrl();
				OCP\JSON::success(array('data' => array(
					'url' => $authUrl
				)));
			} catch (Exception $exception) {
				OCP\JSON::error(array('data' => array(
					'message' => $l->t('Step 1 failed. Exception: %s', array($exception->getMessage()))
				)));
			}
		} else if ($step == 2 && isset($_POST['code'])) {
			try {
				$token = $client->authenticate((string)$_POST['code']);
				OCP\JSON::success(array('data' => array(
					'token' => $token
				)));
			} catch (Exception $exception) {
				OCP\JSON::error(array('data' => array(
					'message' => $l->t('Step 2 failed. Exception: %s', array($exception->getMessage()))
				)));
			}
		}
	}
}

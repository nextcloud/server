<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
require_once __DIR__ . '/../3rdparty/Dropbox/autoload.php';

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
$l = \OC::$server->getL10N('files_external');

// FIXME: currently hard-coded to Dropbox OAuth
if (isset($_POST['app_key']) && isset($_POST['app_secret'])) {
	$oauth = new Dropbox_OAuth_Curl((string)$_POST['app_key'], (string)$_POST['app_secret']);
	if (isset($_POST['step'])) {
		switch ($_POST['step']) {
			case 1:
				try {
					if (isset($_POST['callback'])) {
						$callback = (string)$_POST['callback'];
					} else {
						$callback = null;
					}
					$token = $oauth->getRequestToken();
					OCP\JSON::success(array('data' => array('url' => $oauth->getAuthorizeUrl($callback),
															'request_token' => $token['token'],
															'request_token_secret' => $token['token_secret'])));
				} catch (Exception $exception) {
					OCP\JSON::error(array('data' => array('message' =>
						$l->t('Fetching request tokens failed. Verify that your app key and secret are correct.'))
						));
				}
				break;
			case 2:
				if (isset($_POST['request_token']) && isset($_POST['request_token_secret'])) {
					try {
						$oauth->setToken((string)$_POST['request_token'], (string)$_POST['request_token_secret']);
						$token = $oauth->getAccessToken();
						OCP\JSON::success(array('access_token' => $token['token'],
												'access_token_secret' => $token['token_secret']));
					} catch (Exception $exception) {
						OCP\JSON::error(array('data' => array('message' =>
							$l->t('Fetching access tokens failed. Verify that your app key and secret are correct.'))
							));
					}
				}
				break;
		}
	}
} else {
	OCP\JSON::error(array('data' => array('message' => $l->t('Please provide a valid app key and secret.'))));
}

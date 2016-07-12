<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
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

OCP\JSON::callCheck();
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_sharing');

$l = \OC::$server->getL10N('files_sharing');

$federatedSharingApp = new \OCA\FederatedFileSharing\AppInfo\Application('federatedfilesharing');
$federatedShareProvider = $federatedSharingApp->getFederatedShareProvider();

// check if server admin allows to mount public links from other servers
if ($federatedShareProvider->isIncomingServer2serverShareEnabled() === false) {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Server to server sharing is not enabled on this server'))));
	exit();
}

$token = $_POST['token'];
$remote = $_POST['remote'];
$password = isset($_POST['password']) ? $_POST['password'] : '';

$urlGenerator = \OC::$server->getURLGenerator();

$shareWith = \OCP\User::getUser() . '@' . $urlGenerator->getAbsoluteURL('/');

$httpClient = \OC::$server->getHTTPClientService()->newClient();

try {
	$response = $httpClient->post($remote . '/index.php/apps/federatedfilesharing/saveToNextcloud',
		[
			'body' =>
				[
					'token' => $token,
					'shareWith' => rtrim($shareWith, '/'),
					'password' => $password
				]
		]
	);
} catch (\Exception $e) {
	if (empty($password)) {
		$message = $l->t("Couldn't establish a federated share.");
	} else {
		$message = $l->t("Couldn't establish a federated share, maybe the password was wrong.");
	}
	\OCP\JSON::error(array('data' => array('message' => $message)));
	exit();
}

\OCP\JSON::success(array('data' => array('message' => $l->t('Federated Share request was successful, you will receive a invitation. Check your notifications.'))));

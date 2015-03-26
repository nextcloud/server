<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Florin Peter <github@florin-peter.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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

\OCP\JSON::checkLoggedIn();
\OCP\JSON::checkAppEnabled('files_encryption');
\OCP\JSON::callCheck();

$l = \OC::$server->getL10N('files_encryption');

if (
	isset($_POST['userEnableRecovery'])
	&& (0 == $_POST['userEnableRecovery'] || '1' === $_POST['userEnableRecovery'])
) {

	$userId = \OCP\USER::getUser();
	$view = new \OC\Files\View('/');
	$util = new \OCA\Files_Encryption\Util($view, $userId);

	// Save recovery preference to DB
	$return = $util->setRecoveryForUser((string)$_POST['userEnableRecovery']);

	if ($_POST['userEnableRecovery'] === '1') {
		$util->addRecoveryKeys();
	} else {
		$util->removeRecoveryKeys();
	}

} else {

	$return = false;

}

// Return success or failure
if ($return) {
	\OCP\JSON::success(array('data' => array('message' => $l->t('File recovery settings updated'))));
} else {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Could not update file recovery'))));
}

<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
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

\OC_Util::checkAdminUser();

$template = new OCP\Template('federation', 'settings-admin');

$dbHandler = new \OCA\Federation\DbHandler(
	\OC::$server->getDatabaseConnection(),
	\OC::$server->getL10N('federation')
);

$trustedServers = new \OCA\Federation\TrustedServers(
	$dbHandler,
	\OC::$server->getHTTPClientService(),
	\OC::$server->getLogger(),
	\OC::$server->getJobList(),
	\OC::$server->getSecureRandom(),
	\OC::$server->getConfig(),
	\OC::$server->getEventDispatcher()
);

$template->assign('trustedServers', $trustedServers->getServers());
$template->assign('autoAddServers', $trustedServers->getAutoAddServers());

return $template->fetchPage();

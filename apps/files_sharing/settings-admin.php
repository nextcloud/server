<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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

\OC_Util::checkAdminUser();

\OCP\Util::addScript('files_sharing', 'settings-admin');

$tmpl = new OCP\Template('files_sharing', 'settings-admin');
$tmpl->assign('outgoingServer2serverShareEnabled', OCA\Files_Sharing\Helper::isOutgoingServer2serverShareEnabled());
$tmpl->assign('incomingServer2serverShareEnabled', OCA\Files_Sharing\Helper::isIncomingServer2serverShareEnabled());

return $tmpl->fetchPage();

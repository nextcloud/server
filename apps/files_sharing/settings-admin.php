<?php
/**
 * Copyright (c) 2011 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

\OC_Util::checkAdminUser();

\OCP\Util::addScript('files_sharing', 'settings-admin');

$tmpl = new OCP\Template('files_sharing', 'settings-admin');
$tmpl->assign('outgoingServer2serverShareEnabled', OCA\Files_Sharing\Helper::isOutgoingServer2serverShareEnabled());
$tmpl->assign('incomingServer2serverShareEnabled', OCA\Files_Sharing\Helper::isIncomingServer2serverShareEnabled());

return $tmpl->fetchPage();

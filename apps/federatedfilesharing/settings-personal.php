<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCA\FederatedFileSharing\AppInfo\Application;

\OC_Util::checkLoggedIn();

$l = \OC::$server->getL10N('federatedfilesharing');

$app = new Application('federatedfilesharing');
$federatedShareProvider = $app->getFederatedShareProvider();

$isIE8 = false;
preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
if (count($matches) > 0 && $matches[1] <= 9) {
	$isIE8 = true;
}

$cloudID = \OC::$server->getUserSession()->getUser()->getCloudId();
$url = 'https://owncloud.org/federation#' . $cloudID;
$ownCloudLogoPath = \OC::$server->getURLGenerator()->imagePath('core', 'logo-icon.svg');

$tmpl = new OCP\Template('federatedfilesharing', 'settings-personal');
$tmpl->assign('outgoingServer2serverShareEnabled', $federatedShareProvider->isOutgoingServer2serverShareEnabled());
$tmpl->assign('message_with_URL', $l->t('Share with me through my #ownCloud Federated Cloud ID, see %s', [$url]));
$tmpl->assign('message_without_URL', $l->t('Share with me through my #ownCloud Federated Cloud ID', [$cloudID]));
$tmpl->assign('owncloud_logo_path', $ownCloudLogoPath);
$tmpl->assign('reference', $url);
$tmpl->assign('cloudId', $cloudID);
$tmpl->assign('showShareIT', !$isIE8);

return $tmpl->fetchPage();

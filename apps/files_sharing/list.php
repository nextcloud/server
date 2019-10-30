<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Vincent Petry <pvince81@owncloud.com>
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
use OCA\Files\Event\LoadSidebar;
use OCP\Template;
use OCP\User;
use OCP\Util;

$config = \OC::$server->getConfig();
$eventDispatcher = \OC::$server->getEventDispatcher();
$userSession = \OC::$server->getUserSession();

// Check if we are a user
User::checkLoggedIn();

// various configs
$showgridview = $config->getUserValue($userSession->getUser()->getUID(), 'files', 'show_grid', false);
$isIE = Util::isIE();

// template
$tmpl = new Template('files_sharing', 'list', '');

// gridview not available for ie
$tmpl->assign('showgridview', $showgridview && !$isIE);

Util::addScript('files_sharing', 'dist/files_sharing');
$eventDispatcher->dispatch('\OCP\Collaboration\Resources::loadAdditionalScripts');
$eventDispatcher->dispatch(LoadSidebar::class, new LoadSidebar());

// print output
$tmpl->printPage();

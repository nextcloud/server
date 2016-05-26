<?php
/**
 * @author j-ed <juergen@eisfair.org>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Ross Nicoll <jrn@jrn.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

OC::$CLASSPATH['OC_Mount_Config'] = 'files_external/lib/config.php';

require_once __DIR__ . '/../3rdparty/autoload.php';

// register Application object singleton
\OC_Mount_Config::$app = new \OCA\Files_External\AppInfo\Application();
$appContainer = \OC_Mount_Config::$app->getContainer();

\OC_Mount_Config::$app->registerSettings();

$l = \OC::$server->getL10N('files_external');

\OCA\Files\App::getNavigationManager()->add([
	"id" => 'extstoragemounts',
	"appname" => 'files_external',
	"script" => 'list.php',
	"order" => 30,
	"name" => $l->t('External storage')
]);

$mountProvider = $appContainer->query('OCA\Files_External\Config\ConfigAdapter');
\OC::$server->getMountProviderCollection()->registerProvider($mountProvider);

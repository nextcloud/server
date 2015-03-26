<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
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

OC_Util::checkAdminUser();
\OC::$server->getSession()->close();

// Load the files we need
\OC_Util::addVendorScript('handlebars/handlebars');
\OCP\Util::addScript("settings", "settings");
\OCP\Util::addStyle("settings", "settings");
\OC_Util::addVendorScript('select2/select2');
\OC_Util::addVendorStyle('select2/select2');
\OCP\Util::addScript("settings", "apps");
\OC_App::setActiveNavigationEntry( "core_apps" );

$tmpl = new OC_Template( "settings", "apps", "user" );
$tmpl->printPage();


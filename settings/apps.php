<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2012 Frank Karlitschek frank@owncloud.org
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
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


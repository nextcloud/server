<?php

/**
 * ownCloud - user_ldap
 *
 * @author Dominik Schmidt
 * @author Arthur Schiwon
 * @copyright 2011 Dominik Schmidt dev@dominik-schmidt.de
 * @copyright 2012-2013 Arthur Schiwon blizzz@owncloud.com
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

OCP\Util::addscript('user_ldap', 'settings');
OCP\Util::addstyle('user_ldap', 'settings');

// fill template
$tmpl = new OCP\Template('user_ldap', 'settings');

$prefixes = \OCA\user_ldap\lib\Helper::getServerConfigurationPrefixes();
$hosts = \OCA\user_ldap\lib\Helper::getServerConfigurationHosts();
$tmpl->assign('serverConfigurationPrefixes', $prefixes);
$tmpl->assign('serverConfigurationHosts', $hosts);

// assign default values
$config = new \OCA\user_ldap\lib\Configuration('', false);
$defaults = $config->getDefaults();
foreach($defaults as $key => $default) {
    $tmpl->assign($key.'_default', $default);
}

return $tmpl->fetchPage();

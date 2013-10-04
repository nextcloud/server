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

OCP\Util::addScript('user_ldap', 'settings');
OCP\Util::addScript('core', 'jquery.multiselect');
OCP\Util::addStyle('user_ldap', 'settings');
OCP\Util::addStyle('core', 'jquery.multiselect');
OCP\Util::addStyle('core', 'jquery-ui-1.10.0.custom');

// fill template
$tmpl = new OCP\Template('user_ldap', 'settings');

$prefixes = \OCA\user_ldap\lib\Helper::getServerConfigurationPrefixes();
$hosts = \OCA\user_ldap\lib\Helper::getServerConfigurationHosts();

$wizardHtml = '';
$toc = array();

$wControls = new OCP\Template('user_ldap', 'part.wizardcontrols');
$wControls = $wControls->fetchPage();
$sControls = new OCP\Template('user_ldap', 'part.settingcontrols');
$sControls = $sControls->fetchPage();

$wizard1 = new OCP\Template('user_ldap', 'part.wizard-server');
$wizard1->assign('serverConfigurationPrefixes', $prefixes);
$wizard1->assign('serverConfigurationHosts', $hosts);
$wizard1->assign('wizardControls', $wControls);
$wizardHtml .= $wizard1->fetchPage();
$toc['#ldapWizard1'] = 'Server';

$wizard2 = new OCP\Template('user_ldap', 'part.wizard-userfilter');
$wizard2->assign('wizardControls', $wControls);
$wizardHtml .= $wizard2->fetchPage();
$toc['#ldapWizard2'] = 'User Filter';

$tmpl->assign('tabs', $wizardHtml);
$tmpl->assign('toc', $toc);

$tmpl->assign('serverConfigurationPrefixes', $prefixes);
$tmpl->assign('serverConfigurationHosts', $hosts);
$tmpl->assign('settingControls', $sControls);

// assign default values
$config = new \OCA\user_ldap\lib\Configuration('', false);
$defaults = $config->getDefaults();
foreach($defaults as $key => $default) {
    $tmpl->assign($key.'_default', $default);
}

return $tmpl->fetchPage();

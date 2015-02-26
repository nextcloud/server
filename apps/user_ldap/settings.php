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

OCP\Util::addScript('user_ldap', 'ldapFilter');
OCP\Util::addScript('user_ldap', 'experiencedAdmin');
OCP\Util::addScript('user_ldap', 'settings');
\OC_Util::addVendorScript('user_ldap', 'ui-multiselect/src/jquery.multiselect');
OCP\Util::addStyle('user_ldap', 'settings');
\OC_Util::addVendorStyle('user_ldap', 'ui-multiselect/jquery.multiselect');

// fill template
$tmpl = new OCP\Template('user_ldap', 'settings');

$helper = new \OCA\user_ldap\lib\Helper();
$prefixes = $helper->getServerConfigurationPrefixes();
$hosts = $helper->getServerConfigurationHosts();

$wizardHtml = '';
$toc = array();

$wControls = new OCP\Template('user_ldap', 'part.wizardcontrols');
$wControls = $wControls->fetchPage();
$sControls = new OCP\Template('user_ldap', 'part.settingcontrols');
$sControls = $sControls->fetchPage();

$l = \OC::$server->getL10N('user_ldap');

$wizTabs = array();
$wizTabs[] = array('tpl' => 'part.wizard-server',      'cap' => $l->t('Server'));
$wizTabs[] = array('tpl' => 'part.wizard-userfilter',  'cap' => $l->t('User Filter'));
$wizTabs[] = array('tpl' => 'part.wizard-loginfilter', 'cap' => $l->t('Login Filter'));
$wizTabs[] = array('tpl' => 'part.wizard-groupfilter', 'cap' => $l->t('Group Filter'));
$wizTabsCount = count($wizTabs);
for($i = 0; $i < $wizTabsCount; $i++) {
	$tab = new OCP\Template('user_ldap', $wizTabs[$i]['tpl']);
	if($i === 0) {
		$tab->assign('serverConfigurationPrefixes', $prefixes);
		$tab->assign('serverConfigurationHosts', $hosts);
	}
	$tab->assign('wizardControls', $wControls);
	$wizardHtml .= $tab->fetchPage();
	$toc['#ldapWizard'.($i+1)] = $wizTabs[$i]['cap'];
}

$tmpl->assign('tabs', $wizardHtml);
$tmpl->assign('toc', $toc);
$tmpl->assign('settingControls', $sControls);

// assign default values
$config = new \OCA\user_ldap\lib\Configuration('', false);
$defaults = $config->getDefaults();
foreach($defaults as $key => $default) {
	$tmpl->assign($key.'_default', $default);
}

return $tmpl->fetchPage();

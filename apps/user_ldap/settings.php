<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Dominik Schmidt <dev@dominik-schmidt.de>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Volkan Gezer <volkangezer@gmail.com>
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

OC_Util::checkAdminUser();

// fill template
$tmpl = new OCP\Template('user_ldap', 'settings');

$helper = new \OCA\User_LDAP\Helper();
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
$wizTabs[] = array('tpl' => 'part.wizard-userfilter',  'cap' => $l->t('Users'));
$wizTabs[] = array('tpl' => 'part.wizard-loginfilter', 'cap' => $l->t('Login Attributes'));
$wizTabs[] = array('tpl' => 'part.wizard-groupfilter', 'cap' => $l->t('Groups'));
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
$config = new \OCA\User_LDAP\Configuration('', false);
$defaults = $config->getDefaults();
foreach($defaults as $key => $default) {
	$tmpl->assign($key.'_default', $default);
}

return $tmpl->fetchPage();

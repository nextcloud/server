<?php

/**
 * ownCloud - user_ldap
 *
 * @author Dominik Schmidt
 * @copyright 2011 Dominik Schmidt dev@dominik-schmidt.de
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
$params = array('ldap_host', 'ldap_port', 'ldap_dn', 'ldap_agent_password', 'ldap_base', 'ldap_base_users', 'ldap_base_groups', 'ldap_userlist_filter', 'ldap_login_filter', 'ldap_group_filter', 'ldap_display_name', 'ldap_group_display_name', 'ldap_tls', 'ldap_turn_off_cert_check', 'ldap_nocase', 'ldap_quota_def', 'ldap_quota_attr', 'ldap_email_attr', 'ldap_group_member_assoc_attribute', 'ldap_cache_ttl', 'home_folder_naming_rule');

OCP\Util::addscript('user_ldap', 'settings');
OCP\Util::addstyle('user_ldap', 'settings');

if ($_POST) {
	$clearCache = false;
	foreach($params as $param) {
		if(isset($_POST[$param])) {
			$clearCache = true;
			if('ldap_agent_password' == $param) {
				OCP\Config::setAppValue('user_ldap', $param, base64_encode($_POST[$param]));
			} elseif('home_folder_naming_rule' == $param) {
				$value = empty($_POST[$param]) ? 'opt:username' : 'attr:'.$_POST[$param];
				OCP\Config::setAppValue('user_ldap', $param, $value);
			} else {
				OCP\Config::setAppValue('user_ldap', $param, $_POST[$param]);
			}
		}
		elseif('ldap_tls' == $param) {
			// unchecked checkboxes are not included in the post paramters
			OCP\Config::setAppValue('user_ldap', $param, 0);
		}
		elseif('ldap_nocase' == $param) {
			OCP\Config::setAppValue('user_ldap', $param, 0);
		}
		elseif('ldap_turn_off_cert_check' == $param) {
			OCP\Config::setAppValue('user_ldap', $param, 0);
		}
	}
	if($clearCache){
		$ldap = new \OCA\user_ldap\lib\Connection('user_ldap');
		$ldap->clearCache();
	}
}

// fill template
$tmpl = new OCP\Template( 'user_ldap', 'settings');
foreach($params as $param) {
		$value = OCP\Config::getAppValue('user_ldap', $param,'');
		$tmpl->assign($param, $value);
}

// settings with default values
$tmpl->assign( 'ldap_port', OCP\Config::getAppValue('user_ldap', 'ldap_port', '389'));
$tmpl->assign( 'ldap_display_name', OCP\Config::getAppValue('user_ldap', 'ldap_display_name', 'uid'));
$tmpl->assign( 'ldap_group_display_name', OCP\Config::getAppValue('user_ldap', 'ldap_group_display_name', 'cn'));
$tmpl->assign( 'ldap_group_member_assoc_attribute', OCP\Config::getAppValue('user_ldap', 'ldap_group_member_assoc_attribute', 'uniqueMember'));
$tmpl->assign( 'ldap_agent_password', base64_decode(OCP\Config::getAppValue('user_ldap', 'ldap_agent_password')));
$tmpl->assign( 'ldap_cache_ttl', OCP\Config::getAppValue('user_ldap', 'ldap_cache_ttl', '600'));
$hfnr = OCP\Config::getAppValue('user_ldap', 'home_folder_naming_rule', 'opt:username');
$hfnr = ($hfnr == 'opt:username') ? '' : substr($hfnr, strlen('attr:'));
$tmpl->assign( 'home_folder_naming_rule', $hfnr, '');

return $tmpl->fetchPage();

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

require_once('../../lib/base.php');
require( 'template.php' );

if( !OC_USER::isLoggedIn() || !OC_GROUP::inGroup( OC_USER::getUser(), 'admin' )){
	header( "Location: ".OC_HELPER::linkTo( "index.php" ));
	exit();
}

$params = array('ldap_host', 'ldap_port', 'ldap_dn', 'ldap_password', 'ldap_base', 'ldap_filter');

foreach($params as $param){
	if(isset($_POST[$param])){
		OC_APPCONFIG::setValue('user_ldap', $param, $_POST[$param]);
	}
}
OC_APP::setActiveNavigationEntry( "user_ldap_settings" );


// fill template
$tmpl = new OC_TEMPLATE( 'user_ldap', 'settings', 'admin' );
foreach($params as $param){
		$value = OC_APPCONFIG::getValue('user_ldap', $param,'');
		$tmpl->assign($param, $value);
}

// ldap_port has a default value
$tmpl->assign( 'ldap_port', OC_APPCONFIG::getValue('user_ldap', 'ldap_port', OC_USER_BACKEND_LDAP_DEFAULT_PORT));

$tmpl->printPage();

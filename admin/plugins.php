<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
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

require_once('../lib/base.php');
oc_require( 'template.php' );
if( !OC_USER::isLoggedIn() || !OC_USER::ingroup( $_SESSION['username'], 'admin' )){
	header( "Location: ".OC_UTIL::linkto( "index.php" ));
	exit();
}

$plugins=array();
$blacklist=OC_PLUGIN::loadBlackList();

foreach( OC_PLUGIN::listPlugins() as $i ){
	// Gather data about plugin
	$data = OC_PLUGIN::getPluginData($plugin);

	// Is it enabled?
	$data["enabled"] = ( array_search( $plugin, $blacklist ) === false );

	// Add the data
	$plugins[] = $data;
}


$tmpl = new OC_TEMPLATE( "admin", "plugins", "admin" );
$tmpl->assign( "plugins", $plugins );
$tmpl->printPage();

?>


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
	header( "Location: ".OC_HELPER::linkTo( "index.php" ));
	exit();
}

$adminpages = array();

foreach( OC_APP::getAdminPages() as $i ){
	// Do some more work here soon
	$adminpages[] = $i;
}

$tmpl = new OC_TEMPLATE( "admin", "index", "admin" );
$tmpl->assign( "adminpages", $adminpages );
$tmpl->printPage();

?>


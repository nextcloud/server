<?php

/**
* ownCloud - Sample application
*
* @author Jakob Sack
* @copyright 2011 Jakob Sack kde@jakobsack.de
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

// Do not prepare the file system (for demonstration purpose)
// We HAVE TO set this var before including base.php
$RUNTIME_NOSETUPFS = true;

// Init owncloud
require_once('../lib/base.php');

// We need the file system although we said do not load it! Do it by hand now
OC_Util::setupFS();

// The user should have admin rights. This is an admin page!
if( !OC_User::isLoggedIn() || !OC_User::ingroup( $_SESSION['username'], 'admin' )){
	// Bad boy! Go to the very first page of owncloud
	header( "Location: ".OC_Helper::linkTo( "index.php" ));
	exit();
}

// Do some crazy Stuff over here
$myvar = 2;
$myarray = array( "foo" => array( 0, 1, 2 ), "bar" => "baz" );

// Preparing for output!
$tmpl = new OC_Template( "skeleton", "admin", "admin" ); // Programname, template, mode
// Assign the vars
$tmpl->assign( "var", $myvar );
$tmpl->assign( "array", $myarray );
// Print page
$tmpl->printPage();

?>

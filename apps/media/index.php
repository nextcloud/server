<?php

/**
* ownCloud - media plugin
*
* @author Robin Appelman
* @copyright 2010 Robin Appelman icewind1991@gmail.com
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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/




// Check if we are a user
OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('media');

require_once(OC::$APPSROOT . '/apps/media/lib_collection.php');
require_once(OC::$APPSROOT . '/apps/media/lib_scanner.php');

OCP\Util::addscript('media','player');
OCP\Util::addscript('media','music');
OCP\Util::addscript('media','playlist');
OCP\Util::addscript('media','collection');
OCP\Util::addscript('media','scanner');
OCP\Util::addscript('media','jquery.jplayer.min');
OCP\Util::addStyle('media','music');

OCP\App::setActiveNavigationEntry( 'media_index' );

$tmpl = new OCP\Template( 'media', 'music', 'user' );
$tmpl->printPage();
?>
 

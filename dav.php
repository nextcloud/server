<?php

/**
* ownCloud
*
* @author Jakob Sack
* @copyright 2012 Jakob Sack owncloud@jakobsack.de
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

require_once('lib/base.php');

// Backends we always need (auth, principal and files)
$backends = array(
	'auth' => new OC_Connector_Sabre_Auth(),
	'principal' => new OC_Connector_Sabre_Principal()
);

// Root nodes
$nodes = array(
	new Sabre_CalDAV_Principal_Collection($backends['principal'])
);

// Plugins
$plugins = array(
	new Sabre_DAV_Auth_Plugin($backends['auth'],'ownCloud'),
	new Sabre_DAVACL_Plugin(),
	new Sabre_DAV_Browser_Plugin(false) // Show something in the Browser, but no upload
);

// Load the plugins etc we need for usual file sharing
$backends['lock'] = new OC_Connector_Sabre_Locks();
$plugins[] = new Sabre_DAV_Locks_Plugin($backends['lock']);
// Add a RESTful user directory
// /files/$username/
if( OC_User::isLoggedIn()){
	$currentuser = OC_User::getUser();
	$files = new Sabre_DAV_SimpleCollection('files');
	foreach( OC_User::getUsers() as $username ){
		if( $username == $currentuser ){
			$public = new OC_Connector_Sabre_Directory('.');
			$files->addChild( new Sabre_DAV_SimpleCollection( $username, $public->getChildren()));
		}
		else{
			$files->addChild(new Sabre_DAV_SimpleCollection( $username ));
		}
	}
	$nodes[] = $files;
}

// Get the other plugins and nodes
OC_Hook::emit( 'OC_DAV', 'initialize', array( 'backends' => &$backends, 'nodes' => &$nodes, 'plugins' => &$plugins ));

// Fire up server
$server = new Sabre_DAV_Server($nodes);
$server->setBaseUri(OC::$WEBROOT.'/dav.php');

// Load additional plugins
foreach( $plugins as &$plugin ){
	$server->addPlugin( $plugin );
} unset( $plugin ); // Always do this after foreach with references!

// And off we go!
$server->exec();

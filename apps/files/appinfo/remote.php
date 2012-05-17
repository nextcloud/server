<?php

/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2010 Frank Karlitschek karlitschek@kde.org
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
// only need filesystem apps
$RUNTIME_APPTYPES=array('filesystem','authentication');
OC_App::loadApps($RUNTIME_APPTYPES);

// Backends
$authBackend = new OC_Connector_Sabre_Auth();
$lockBackend = new OC_Connector_Sabre_Locks();

// Create ownCloud Dir
$publicDir = new OC_Connector_Sabre_Directory('');

// Fire up server
$server = new Sabre_DAV_Server($publicDir);
$server->setBaseUri($baseuri);

// Load plugins
$server->addPlugin(new Sabre_DAV_Auth_Plugin($authBackend,'ownCloud'));
$server->addPlugin(new Sabre_DAV_Locks_Plugin($lockBackend));
$server->addPlugin(new Sabre_DAV_Browser_Plugin(false)); // Show something in the Browser, but no upload

// And off we go!
$server->exec();

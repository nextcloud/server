<?php

/**
* ownCloud - bookmarks plugin
*
* @author Arthur Schiwon
* @copyright 2011 Arthur Schiwon blizzz@arthur-schiwon.de
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
OCP\App::checkAppEnabled('bookmarks');

OCP\App::setActiveNavigationEntry( 'bookmarks_index' );

OCP\Util::addscript('bookmarks','bookmarks');
OCP\Util::addStyle('bookmarks', 'bookmarks');

$tmpl = new OCP\Template( 'bookmarks', 'list', 'user' );

$tmpl->printPage();

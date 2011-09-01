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

OC_App::register( array( 'order' => 70, 'id' => 'bookmark', 'name' => 'Bookmarks' ));

OC_App::addNavigationEntry( array( 'id' => 'bookmarks_index', 'order' => 70, 'href' => OC_Helper::linkTo( 'bookmarks', 'index.php' ), 'icon' => OC_Helper::imagePath( 'bookmarks', 'bookmarks.png' ), 'name' => 'Bookmarks' ));


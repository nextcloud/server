<?php

/**
* ownCloud - ATNotes plugin
*
* @author Xavier Beurois
* @copyright 2012 Xavier Beurois www.djazz-lab.net
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

OCP\App::checkAppEnabled('atnotes');
OC::$CLASSPATH['OC_ATNotes'] = 'apps/atnotes/lib/atnotes.class.php';

OCP\App::register(Array(
	'order' => 29,
	'id' => 'atnotes',
	'name' => 'ATNotes'
));

OCP\App::addNavigationEntry(Array(
	'id' => 'atnotes_index',
	'order' => 29,
	'href' => OCP\Util::linkTo('atnotes', 'atnotes.php'),
	'icon' => OCP\Util::imagePath('atnotes', 'icon.png'),
	'name' => 'ATNotes'
));
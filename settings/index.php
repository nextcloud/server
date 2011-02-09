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

$CONFIG_ERROR='';

require_once('../inc/lib_base.php');


OC_UTIL::showheader();

$FIRSTRUN=false;

OC_CONFIG::addForm('User Settings','/inc/templates/configform.php');
if(OC_USER::ingroup($_SESSION['username'],'admin')){
	OC_CONFIG::addForm('System Settings','/inc/templates/adminform.php');
	OC_CONFIG::addForm('User Management','/inc/templates/userform.php');
	OC_CONFIG::addForm('Plugin Management','/inc/templates/pluginform.php');
}

echo('<div class="center">');
OC_CONFIG::showSettings();
echo('</div>');


OC_UTIL::showfooter();

?>

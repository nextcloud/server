<?php

/**
* ownCloud - ajax frontend
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
require_once('../inc/lib_base.php');

$sourceDir=$_GET['sourcedir'];
$targetDir=$_GET['targetdir'];
$source=$_GET['source'];
$target=$_GET['target'];
if(isset($_SESSION['username']) and $_SESSION['username'] and strpos($sourceDir,'..')===false and strpos($source,'..')===false and strpos($targetDir,'..')===false and strpos($target,'..')===false){
	$target=$CONFIG_DATADIRECTORY.'/'.$targetDir.'/'.$target.'/'.$source;
	$source=$CONFIG_DATADIRECTORY.'/'.$sourceDir.'/'.$source;
	rename($source,$target);
}

?>
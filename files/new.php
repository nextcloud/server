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

$dir=$_GET['dir'];
$name=$_GET['name'];
$type=$_GET['type'];
if(isset($_SESSION['username']) and $_SESSION['username'] and strpos($dir,'..')===false and strpos($name,'..')===false){
	$file=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$name;
	if($type=='dir'){
		mkdir($file);
	}elseif($type=='file'){
		$fileHandle=fopen($file, 'w') or die("can't open file");
		fclose($fileHandle);
	}
}

?>
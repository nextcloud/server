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

// sleep(5); //immitate slow internet.

$fileName=$_FILES['file']['name'];
$source=$_FILES['file']['tmp_name'];
$target=$CONFIG_DATADIRECTORY.'/'.$_GET['dir'].'/'.$fileName;
if(isset($_SESSION['username']) and $_SESSION['username'] and strpos($_GET['dir'],'..')===false){
   if(move_uploaded_file($source,$target)){
      echo 'true';
   }else{
      echo 'false';
   }
}else{
   echo 'false';
}
?>
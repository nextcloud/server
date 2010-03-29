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

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

// header('Content-type: text/plain');
header('Content-type: application/xml');

$dir=isset($_GET['dir'])?$_GET['dir']:'';
$files=OC_FILES::getdirectorycontent($CONFIG_DATADIRECTORY.'/'.$dir);
$dirname=$files[0]['directory'];
$dirname=substr($dirname,strrpos($dirname,'/'));
$max_upload=min(return_bytes(ini_get('post_max_size')),return_bytes(ini_get('upload_max_filesize')));
ob_clean();
echo "<?xml version='1.0' standalone='yes'?>\n";
echo "<dir name='$dirname' max_upload='$max_upload'>\n";
foreach($files as $file){
   $attributes='';
   foreach($file as $name=>$data){
      $data=str_replace("'",'&#39;',$data);
      if (is_string($name)) $attributes.=" $name='$data'";
   }
   $attributes.=' date=\''.date($CONFIG_DATEFORMAT,$file['mtime']).'\'';
   echo "<file$attributes/>\n";
}
echo "</dir>";
?>
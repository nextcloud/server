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

//not this file is for getting files themselves, get_files.php is for getting a list of files.

require_once('../inc/lib_base.php');

function get_mime_type($filename, $mimePath = '../etc') { 
   $fileext = substr(strrchr($filename, '.'), 1); 
   if (empty($fileext)) return (false); 
   $regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i"; 
   $lines = file("$mimePath/mime.types"); 
   foreach($lines as $line) { 
      if (substr($line, 0, 1) == '#') continue; // skip comments 
      $line = rtrim($line) . " "; 
      if (!preg_match($regex, $line, $matches)) continue; // no match to the extension 
      return ($matches[1]); 
   } 
   return (false); // no match at all 
} 

$file=$_GET['file'];
$dir=(isset($_GET['dir']))?$_GET['dir']:'';
if(strstr($file,'..') or strstr($dir,'..')){
    die();
}
$filename=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$file;
// $ftype='application/octet-stream'; 
// $finfo=new finfo(FILEINFO_MIME); 
// $fres=@$finfo->file($filename); 
// if (is_string($fres) && !empty($fres)) { 
//    $ftype = $fres; 
// }
$ftype=get_mime_type($filename);
ob_end_clean();
// echo $ftype;
// die();
header('Content-Type: '.$ftype);
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filename));
readfile($filename);
?>
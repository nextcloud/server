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

if(!function_exists('sys_get_temp_dir')) {
    function sys_get_temp_dir() {
        if( $temp=getenv('TMP') )        return $temp;
        if( $temp=getenv('TEMP') )        return $temp;
        if( $temp=getenv('TMPDIR') )    return $temp;
        $temp=tempnam(__FILE__,'');
        if (file_exists($temp)) {
          unlink($temp);
          return dirname($temp);
        }
        return null;
    }
}

function addDir($dir,$zip,$internalDir=''){
    $dirname=basename($dir);
    $zip->addEmptyDir($internalDir.$dirname);
    $internalDir.=$dirname.='/';
    $files=OC_FILES::getdirectorycontent($dir);
    foreach($files as $file){
        $filename=$file['name'];
        $file=$dir.'/'.$filename;
        if(is_file($file)){
            $zip->addFile($file,$internalDir.$filename);
        }elseif(is_dir($file)){
            addDir($file,$zip,$internalDir);
        }
    }
}

$files=$_GET['files'];
$dir=(isset($_GET['dir']))?$_GET['dir']:'';
if(strstr($files,'..') or strstr($dir,'..')){
    die();
}
if(strpos($files,',')){
    $files=explode(',',$files);
}


if(is_array($files)){
    $zip = new ZipArchive();
    $filename = sys_get_temp_dir()."/ownCloud.zip";
    if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
        exit("cannot open <$filename>\n");
    }
    foreach($files as $file){
        $file=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$file;
        if(is_file($file)){
            $zip->addFile($file,basename($file));
        }elseif(is_dir($file)){
            addDir($file,$zip);
        }
    }
    $zip->close();
}elseif(is_dir($CONFIG_DATADIRECTORY.'/'.$dir.'/'.$files)){
    $zip = new ZipArchive();
    $filename = sys_get_temp_dir()."/ownCloud.zip";
    if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
        exit("cannot open <$filename>\n");
    }
    $file=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$files;
    addDir($file,$zip);
    $zip->close();
}else{
    $zip=false;
    $filename=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$files;
}
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename='.basename($filename));
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filename));
ob_clean();
readfile($filename);
if($zip){
    unlink($filename);
}
?>
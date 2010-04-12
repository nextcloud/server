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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/



/**
 * Class for fileserver access
 *
 */
class OC_FILES {

  /**
   * show a web GUI filebrowser
   *
   * @param basedir $basedir
   * @param dir $dir
   */
 public static function showbrowser($basedir,$dir){/*
    global $CONFIG_DATEFORMAT;
    global $WEBROOT;

    $directory=$basedir.'/'.$dir;

    // exit if try to access files outside our directory
    if(strstr($dir,'..')<>false) exit();
    $directory=realpath($directory);

    $dirs=explode('/',$dir);

    // breadcrumb
    if(count($dirs)>1) {
      echo('<div class="center"><table cellpadding="2" cellspacing="0" border="0"><tr>');
      echo('<td class="nametext"><a href="'.$WEBROOT.'/">home</a></td>');
      $currentdir='';
      foreach($dirs as $d) {
        $currentdir.='/'.$d.'';
        if($d<>'') echo('<td class="nametext"><a href="'.$WEBROOT.'/?dir='.$currentdir.'"><img src="'.$WEBROOT.'/img/arrow.png" />&nbsp;'.$d.'</a></td>');
      }
      echo('</tr></table></div>');
    }

    // files and directories
    echo('<div class="center"><table cellpadding="6" cellspacing="0" border="0" class="browser">');
    $filesfound=false;
    $content=self::getdirectorycontent($directory);
    if($content){
       foreach($content as $file){
          echo('<tr class="browserline">');
          OC_UTIL::showicon($file['type']);
          if($file['type']=='dir') echo('<td class="nametext"><a href="'.$WEBROOT.'/?dir='.$dir.'/'.$file['name'].'">'.$file['name'].'</a></td>');
          if($file['type']<>'dir') echo('<td class="nametext"><a href="'.$WEBROOT.'/?dir='.$dir.'&file='.$file['name'].'">'.$file['name'].'</a></td>');
          if($file['type']<>'dir') echo('<td class="sizetext">'.$file['size'].' byte</td>'); else echo('<td></td>');
          echo('<td class="sizetext">'.date($CONFIG_DATEFORMAT,$file['mtime']).'</td>');
          echo('</tr>');
       }
    }
    echo('</table>');
    if(!$content) echo('<p>no files here</p>');
    echo('</div>');*/
    echo '<div id="content"></div>';
  }
  
  /**
   * get the content of a directory
   * @param dir $directory
   */
  public static function getdirectorycontent($directory){
     $filesfound=true;
     $content=array();
     $dirs=array();
     $file=array();
     if (is_dir($directory)) {
      if ($dh = opendir($directory)) {
        while (($filename = readdir($dh)) !== false) {
          if($filename<>'.' and $filename<>'..'){
            $file=array();
            $filesfound=true;
            $file['name']=$filename;
            $file['directory']=$directory;
            $stat=stat($directory.'/'.$filename);
            $file=array_merge($file,$stat);
            $file['type']=filetype($directory .'/'. $filename);
            if($file['type']=='dir'){
               $dirs[$file['name']]=$file;
            }else{
               $files[$file['name']]=$file;
            }
          }
        }
      closedir($dh);
      }
    }
    ksort($dirs);
    ksort($files);
    $content=array_merge($dirs,$files);
    if($filesfound){
       return $content;
    }else{
       return false;
    }
  }



  /**
   * return the cntent of a file
   *
   * @param dir  $dir
   * @param file $file
   */
  public static function get($dir,$file){
    if(isset($_SESSION['username']) and $_SESSION['username']<>'') {
      global $CONFIG_DATADIRECTORY;
      $filename=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$file;

      // exit if try to access files outside our directory
      if(strstr($filename,'..')<>false) exit();

      OC_LOG::event($_SESSION['username'],3,$dir.'/'.$file);

      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename='.basename($file));
      header('Content-Transfer-Encoding: binary');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
      header('Content-Length: ' . filesize($filename));
      readfile($filename);
    }
    exit;
  }


}



?>

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

require_once('../../../lib/base.php');
require_once('../../../lib/template.php');

OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('atnotes');

$p = trim($_POST['p']);
$top = FALSE;
if($p == '/'){
	$p = OC::$CONFIG_DATADIRECTORY;
	$top = TRUE;
}

$files = Array();
foreach(OC_Files::getdirectorycontent($p) as $i){
	$elt = '';
	
	if($i['type'] == 'file'){
		$fileinfo = pathinfo($i['name']);
		$i['basename'] = $fileinfo['filename'];
		if (!empty($fileinfo['extension'])){
			$i['extention'] = '.'.$fileinfo['extension'];
		}else{
			$i['extention'] = '';
		}
	}
	if($i['directory'] == '/'){
		$i['directory'] = '';
	}
	if($i['extention'] == '.txt' || $i['type'] == 'dir'){
		$i["date"] = OC_Util::formatDate($i["mtime"]);
		
		$write = ($i['writeable'])?'true':'false';
		$simple_file_size = simple_file_size($i['size']);
		$simple_size_color = intval(200 - $i['size'] / (1024 * 1024) * 2);
		
		if($simple_size_color < 0){
			$simple_size_color = 0;
		}
		
		$relative_modified_date = relative_modified_date($i['mtime']);
		$relative_date_color = round((time() - $i['mtime']) / 60 / 60 / 24 * 14);
		
		if($relative_date_color > 200){
			$relative_date_color = 200;
		}
		
		$name = str_replace('+', '%20', urlencode($i['name']));
		$name = str_replace('%2F', '/', $name);
		$directory = str_replace('+', '%20', urlencode($i['directory']));
		$directory = str_replace('%2F', '/', $directory);
		
		$elt .= '<tr data-file="'.$name.'" data-type="'.(($i['type'] == 'dir')?'dir':'file').'" data-mime="'.$i['mime'].'" data-size="'.$i['size'].'" data-write="'.$write.'">';
		$elt .= '<td class="filename svg" data-rel="'.$directory.'/'.$name.'" style="background-image:url('.(($i['type'] == 'dir')?mimetype_icon('dir'):mimetype_icon($i['mime'])).')">';
		$elt .= '<span class="nametext">';
		if($i['type'] == 'dir'){
			$elt .= htmlspecialchars($i['name']);
		}else{
			$elt .= htmlspecialchars($i['basename']).'<span class="extention">'.$i['extention'].'</span>';
		}
		$elt .= '</span>';
		$elt .= '</td>';
		$elt .= '<td class="filesize" title="'.human_file_size($i['size']).'" style="color:rgb('.$simple_size_color.','.$simple_size_color.','.$simple_size_color.')">'.$simple_file_size.'</td>';
		$elt .= '<td class="date"><span class="modified" title="'.$i['date'].'" style="color:rgb('.$relative_date_color.','.$relative_date_color.','.$relative_date_color.')">'.$relative_modified_date.'</span></td>';
		$elt .= '</tr>';
				
		$files[] = $elt;
	}
}

if(!$top){
	$p = str_replace('+', '%20', urlencode($p));
	$p = str_replace('%2F', '/', $p);
	$p = substr($p,0,strrpos($p,'/'));
	if(strlen($p) == 0){
		$p = '/';
	}
	
	$elt = '<tr>';
	$elt .= '<td class="filename svg" data-rel="'.$p.'" style="background-image:url('.mimetype_icon('dir').')">';
	$elt .= '<span class="nametext">..</span>';
	$elt .= '</td>';
	$elt .= '<td class="filesize">&nbsp;</td>';
	$elt .= '<td class="date">&nbsp;</td>';
	$elt .= '</tr>';
	array_unshift($files, $elt);
}

OC_JSON::encodedPrint($files);
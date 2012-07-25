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

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('atnotes');

$p = trim($_POST['p']);
$t = trim($_POST['t']);

$normalizeChars = array(
	'Á'=>'A', 'À'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Å'=>'A', 'Ä'=>'A', 'Æ'=>'AE', 'Ç'=>'C',
	'É'=>'E', 'È'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Í'=>'I', 'Ì'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ð'=>'Eth',
	'Ñ'=>'N', 'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O',
	'Ú'=>'U', 'Ù'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y',
	'á'=>'a', 'à'=>'a', 'â'=>'a', 'ã'=>'a', 'å'=>'a', 'ä'=>'a', 'æ'=>'ae', 'ç'=>'c',
	'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e', 'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'eth',
	'ñ'=>'n', 'ó'=>'o', 'ò'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o',
	'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y',
	'ß'=>'sz', 'þ'=>'thorn', 'ÿ'=>'y', ' ' => '_', '"' => '', "'" => ' ', '/' => '-'
);

$r = Array('e' => FALSE, 'p' => '');
if(strlen($p) != 0 && strlen($t) != 0){
	$fs = OCP\Files::getStorage('files');
	if($fs->is_dir($p)){
		$r['p'] = $p.'/'.strtr($t, $normalizeChars).'.txt';
	}else{
		$r['p'] = $p;
	}
	
	if($fs->file_exists($r['p'])){
		$r['e'] = TRUE;
	}
}

OCP\JSON::encodedPrint($r);

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
$c = trim($_POST['c']);

$r = Array('e' => '');
if(strlen($p) != 0 && strlen($t) != 0 && strlen($c) != 0){
	$fs = OCP\Files::getStorage('files');
	if(!$fp = $fs->fopen($p, 'w')){
		$r['e'] = 'Can not open file '.$p;
	}else{
		 if(fwrite($fp, $t."\n") === FALSE){
		 	$r['e'] = 'Can not write to file '.$p;
		 }else{
		 	$c = preg_replace('/<br[.*]{0,}>/',"\n",$c);
		 	$c = preg_replace('/<u[.*]{0,}>/','',$c);$c = preg_replace('/<\/u>/','',$c);
			$c = preg_replace('/<b[.*]{0,}>/','',$c);$c = preg_replace('/<\/b>/','',$c);
			$c = preg_replace('/<i[.*]{0,}>/','',$c);$c = preg_replace('/<\/i>/','',$c);
			$c = preg_replace('/<hr.*>/',"------------------------\n",$c);
			$c = preg_replace('/<sup[.*]{0,}>/','(',$c);$c = preg_replace('/<\/sup>/',')',$c);
			$c = preg_replace('/<sub[.*]{0,}>/','(',$c);$c = preg_replace('/<\/sub>/',')',$c);
		 	if(fwrite($fp, $c) === FALSE){
			 	$r['e'] = 'Can not write to file '.$p;
			 }else{
			 	fclose($fp);
			 }
		 }
	}
}

OCP\JSON::encodedPrint($r);

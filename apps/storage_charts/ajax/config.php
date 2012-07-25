<?php

/**
* ownCloud - DjazzLab Storage Charts plugin
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
OCP\JSON::checkAppEnabled('storage_charts');

if(in_array($_POST['k'], Array('hu_size','sc_sort','hu_size_hus'))){
	switch($_POST['o']){
		case 'set':
			$i = NULL;
			if(is_array($_POST['i'])){
				$i = serialize($_POST['i']);
				
			}elseif(is_numeric($_POST['i'])){
				$i = $_POST['i'];
			}
			OC_DLStCharts::setUConfValue($_POST['k'], $i);
		break;
		case 'get':
			$v = OC_DLStCharts::getUConfValue($_POST['k']);
			OCP\JSON::encodedPrint(Array('r' => $v['uc_val']));
		break;
	}
}

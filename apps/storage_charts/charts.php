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

OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('storage_charts');

$tmpl = new OCP\Template('storage_charts', 'charts.tpl', 'user');

// Get data for all users if admin or just for the current user
$displays = OC_DLStCharts::getUConfValue('c_disp', Array('uc_val' => 'a:3:{s:10:"cpie_rfsus";i:1;s:11:"clines_usse";i:1;s:9:"chisto_us";i:1;}'));
$displays = unserialize($displays['uc_val']);
$tmpl->assign('c_disp', $displays);

$sc_sort = OC_DLStCharts::getUConfValue('sc_sort', Array('uc_val' => 'a:3:{i:0;s:10:"cpie_rfsus";i:1;s:11:"clines_usse";i:2;s:9:"chisto_us";}'));
$tmpl->assign('sc_sort', unserialize($sc_sort['uc_val']));

if($displays['clines_usse']){
	$hu_size = OC_DLStCharts::getUConfValue('hu_size', Array('uc_val' => 3));
	$tmpl->assign('hu_size', $hu_size['uc_val']);
}
if($displays['chisto_us']){
	$hu_size_hus = OC_DLStCharts::getUConfValue('hu_size_hus', Array('uc_val' => 3));
	$tmpl->assign('hu_size_hus', $hu_size_hus['uc_val']);
}

$tmpl->printPage();

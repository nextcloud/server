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

OCP\App::checkAppEnabled('storage_charts');

OC::$CLASSPATH['OC_DLStCharts'] = "apps/storage_charts/lib/db.class.php";
OC::$CLASSPATH['OC_DLStChartsLoader'] = "apps/storage_charts/lib/loader.class.php";

OCP\App::register(Array(
	'order' => 60,
	'id' => 'storage_charts',
	'name' => 'Storage Charts'
));

OCP\App::addNavigationEntry(Array(
	'id' => 'storage_charts',
	'order' => 60,
	'href' => OCP\Util::linkTo('storage_charts', 'charts.php'),
	'icon' => OCP\Util::imagePath('storage_charts', 'chart.png'),
	'name' => 'DL Charts'
));

OCP\App::registerPersonal('storage_charts','settings');

$data_dir = OCP\Config::getSystemValue('datadirectory', '');
if(OCP\User::getUser() && strlen($data_dir) != 0){
	$fs = OCP\Files::getStorage('files');
	$used = OC_DLStCharts::getTotalDataSize(OC::$CONFIG_DATADIRECTORY);
	$total = OC_DLStCharts::getTotalDataSize($data_dir) + $fs->free_space();
	OC_DLStCharts::update($used, $total);
}

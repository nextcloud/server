<?php

/**
 * ownCloud - External plugin
 *
 * @author Frank Karlitschek
 * @copyright 2011 Frank Karlitschek karlitschek@kde.org
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

OC::$CLASSPATH['OC_External'] = 'apps/external/lib/external.php';
OCP\Util::addStyle( 'external', 'style');

OCP\App::registerAdmin('external', 'settings');

OCP\App::register(array('order' => 70, 'id' => 'external', 'name' => 'External'));

$sites = OC_External::getSites();
for ($i = 0; $i < sizeof($sites); $i++) {
	OCP\App::addNavigationEntry(
			array('id' => 'external_index' . ($i + 1), 'order' => 80 + $i, 'href' => OCP\Util::linkTo('external', 'index.php') . '?id=' . ($i + 1), 'icon' => OCP\Util::imagePath('external', 'external.png'), 'name' => $sites[$i][0]));
}

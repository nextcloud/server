<?php

/**
* ownCloud
*
* @author Arthur Schiwon
* @copyright 2013 Arthur Schiwon blizzz@owncloud.com
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
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

$RUNTIME_NOAPPS = true; //no apps, yet

require_once 'lib/base.php';

// Don't do anything if ownCloud has not been installed
if(!OC_Config::getValue('installed', false)) {
	exit(0);
}

$br = OC::$CLI ? PHP_EOL : '<br/>';

if(OC::checkUpgrade(false)) {
	$updater = new \OC_Updater();

	$updater->listen('\OC_Updater', 'maintenanceStart', function () use ($br) {
		echo 'Turned on maintenance mode'.$br;
	});
	$updater->listen('\OC_Updater', 'maintenanceEnd', function () use ($br) {
		echo 'Turned off maintenance mode'.$br;
		echo 'Update successful'.$br;
	});
	$updater->listen('\OC_Updater', 'dbUpgrade', function () use ($br) {
		echo 'Updated database'.$br;
	});
	$updater->listen('\OC_Updater', 'filecacheStart', function () use ($br) {
		echo 'Updating filecache, this may take really long...'.$br;
	});
	$updater->listen('\OC_Updater', 'filecacheDone', function () use ($br) {
		echo 'Updated filecache'.$br;
	});
	$updater->listen('\OC_Updater', 'filecacheProgress', function ($out)
		use ($br) {
		echo '... ' . $out . '% done ...'.$br;
	});

	$updater->listen('\OC_Updater', 'failure', function ($message) use ($br) {
		echo $message.$br;
		OC_Config::setValue('maintenance', false);
	});

	$updater->upgrade();
} else {
	if(OC_Config::getValue('maintenance', false)) {
		//Possible scenario: ownCloud core is updated but an app failed
		echo 'ownCloud is in maintenance mode'.$br;
		echo 'Maybe an upgrade is already in process. Please check the '
			. 'logfile (data/owncloud.log). If you want to re-run the '
			. 'upgrade procedure, remove the "maintenance mode" from '
			. 'config.php and call this script again.'
			.$br;
	} else {
		echo 'ownCloud is already latest version'.$br;
	}
}

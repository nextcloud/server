<?php
/**
* ownCloud
*
* @author Jakob Sack
* @copyright 2012 Jakob Sack owncloud@jakobsack.de
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

// Unfortunately we need this class for shutdown function
class my_temporary_cron_class {
	public static $sent = false;
}

function handleUnexpectedShutdown() {
	if( !my_temporary_cron_class::$sent ) {
		if( OC::$CLI ) {
			echo 'Unexpected error!'.PHP_EOL;
		}
		else{
			OC_JSON::error( array( 'data' => array( 'message' => 'Unexpected error!')));
		}
	}
}

$RUNTIME_NOSETUPFS = true;
require_once 'lib/base.php';

// Don't do anything if ownCloud has not been installed
if( !OC_Config::getValue( 'installed', false )) {
	exit( 0 );
}

// Handle unexpected errors
register_shutdown_function('handleUnexpectedShutdown');

// Exit if background jobs are disabled!
$appmode = OC_Appconfig::getValue( 'core', 'backgroundjobs_mode', 'ajax' );
if( $appmode == 'none' ) {
	my_temporary_cron_class::$sent = true;
	if( OC::$CLI ) {
		echo 'Background Jobs are disabled!'.PHP_EOL;
	}
	else{
		OC_JSON::error( array( 'data' => array( 'message' => 'Background jobs disabled!')));
	}
	exit( 1 );
}

if( OC::$CLI ) {
	if( $appmode != 'cron' ) {
		OC_Appconfig::setValue( 'core', 'backgroundjobs_mode', 'cron' );
	}

	// check if backgroundjobs is still running
	$pid = OC_Appconfig::getValue( 'core', 'backgroundjobs_pid', false );
	if( $pid !== false ) {
		// FIXME: check if $pid is still alive (*nix/mswin). if so then exit
	}
	// save pid
	OC_Appconfig::setValue( 'core', 'backgroundjobs_pid', getmypid());

	// Work
	OC_BackgroundJob_Worker::doAllSteps();
}
else{
	if( $appmode == 'cron' ) {
		OC_JSON::error( array( 'data' => array( 'message' => 'Backgroundjobs are using system cron!')));
	}
	else{
		OC_BackgroundJob_Worker::doNextStep();
		OC_JSON::success();
	}
}
my_temporary_cron_class::$sent = true;
exit();

<?php
/**
 * @author Boris Rybalkin <ribalkin@gmail.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Oliver Kohl D.Sc. <oliver@kohl.bz>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Steffen Lindner <mail@steffen-lindner.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
// Unfortunately we need this class for shutdown function
class TemporaryCronClass {
	public static $sent = false;
	public static $lockfile = "";
	public static $keeplock = false;
}

// We use this function to handle (unexpected) shutdowns
function handleUnexpectedShutdown() {
	// Delete lockfile
	if (!TemporaryCronClass::$keeplock && file_exists(TemporaryCronClass::$lockfile)) {
		unlink(TemporaryCronClass::$lockfile);
	}

	// Say goodbye if the app did not shutdown properly
	if (!TemporaryCronClass::$sent) {
		if (OC::$CLI) {
			echo 'Unexpected error!' . PHP_EOL;
		} else {
			OC_JSON::error(array('data' => array('message' => 'Unexpected error!')));
		}
	}
}

try {

	require_once 'lib/base.php';

	if (\OCP\Util::needUpgrade()) {
		\OCP\Util::writeLog('cron', 'Update required, skipping cron', \OCP\Util::DEBUG);
		exit();
	}

	// load all apps to get all api routes properly setup
	OC_App::loadApps();

	\OC::$server->getSession()->close();

	// initialize a dummy memory session
	\OC::$server->setSession(new \OC\Session\Memory(''));

	$logger = \OC_Log::$object;

	// Don't do anything if ownCloud has not been installed
	if (!OC_Config::getValue('installed', false)) {
		exit(0);
	}

	// Handle unexpected errors
	register_shutdown_function('handleUnexpectedShutdown');

	\OC::$server->getTempManager()->cleanOld();

	// Exit if background jobs are disabled!
	$appmode = OC_BackgroundJob::getExecutionType();
	if ($appmode == 'none') {
		TemporaryCronClass::$sent = true;
		if (OC::$CLI) {
			echo 'Background Jobs are disabled!' . PHP_EOL;
		} else {
			OC_JSON::error(array('data' => array('message' => 'Background jobs disabled!')));
		}
		exit(1);
	}

	if (OC::$CLI) {
		// Create lock file first
		TemporaryCronClass::$lockfile = OC_Config::getValue("datadirectory", OC::$SERVERROOT . '/data') . '/cron.lock';

		// We call ownCloud from the CLI (aka cron)
		if ($appmode != 'cron') {
			// Use cron in future!
			OC_BackgroundJob::setExecutionType('cron');
		}

		// check if backgroundjobs is still running
		if (file_exists(TemporaryCronClass::$lockfile)) {
			TemporaryCronClass::$keeplock = true;
			TemporaryCronClass::$sent = true;
			echo "Another instance of cron.php is still running!" . PHP_EOL;
			exit(1);
		}

		// Create a lock file
		touch(TemporaryCronClass::$lockfile);

		// Work
		$jobList = \OC::$server->getJobList();
		$jobs = $jobList->getAll();
		foreach ($jobs as $job) {
			$job->execute($jobList, $logger);
		}
	} else {
		// We call cron.php from some website
		if ($appmode == 'cron') {
			// Cron is cron :-P
			OC_JSON::error(array('data' => array('message' => 'Backgroundjobs are using system cron!')));
		} else {
			// Work and success :-)
			$jobList = \OC::$server->getJobList();
			$job = $jobList->getNext();
			if ($job != null) {
				$job->execute($jobList, $logger);
				$jobList->setLastJob($job);
			}
			OC_JSON::success();
		}
	}

	// done!
	TemporaryCronClass::$sent = true;
	// Log the successful cron execution
	if (\OC::$server->getConfig()->getSystemValue('cron_log', true)) {
		\OC::$server->getAppConfig()->setValue('core', 'lastcron', time());
	}
	exit();

} catch (Exception $ex) {
	\OCP\Util::writeLog('cron', $ex->getMessage(), \OCP\Util::FATAL);
}

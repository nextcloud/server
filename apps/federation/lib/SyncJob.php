<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

namespace OCA\Federation;

use OC\BackgroundJob\TimedJob;
use OCA\Federation\AppInfo\Application;

class SyncJob extends TimedJob {

	public function __construct() {
		// Run once a day
		$this->setInterval(24 * 60 * 60);
	}

	protected function run($argument) {
		$app = new Application();
		$ss = $app->getSyncService();
		$ss->syncThemAll(function($url, $ex) {
			if ($ex instanceof \Exception) {
				\OC::$server->getLogger()->error("Error while syncing $url : " . $ex->getMessage(), ['app' => 'fed-sync']);
			}
		});
	}
}

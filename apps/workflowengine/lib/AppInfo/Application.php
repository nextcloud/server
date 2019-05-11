<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\WorkflowEngine\AppInfo;

use OCP\Template;
use OCA\WorkflowEngine\Controller\RequestTime;
use OCA\WorkflowEngine\Controller\FlowOperations;

class Application extends \OCP\AppFramework\App {

	public function __construct() {
		parent::__construct('workflowengine');

		$this->getContainer()->registerAlias('FlowOperationsController', FlowOperations::class);
		$this->getContainer()->registerAlias('RequestTimeController', RequestTime::class);
	}

	/**
	 * Register all hooks and listeners
	 */
	public function registerHooksAndListeners() {
		$dispatcher = $this->getContainer()->getServer()->getEventDispatcher();
		$dispatcher->addListener(
			'OCP\WorkflowEngine::loadAdditionalSettingScripts',
			function() {
				if (!function_exists('style')) {
					// This is hacky, but we need to load the template class
					class_exists(Template::class, true);
				}

				style('workflowengine', [
					'admin',
				]);

				script('core', [
					'files/fileinfo',
					'files/client',
					'systemtags/systemtags',
					'systemtags/systemtagmodel',
					'systemtags/systemtagscollection',
				]);

				script('workflowengine', [
					'workflowengine',
				]);
			},
			-100
		);
	}
}

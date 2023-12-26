<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author François Freitag <mail@franek.fr>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\WorkflowEngine\Listener;

use OCA\WorkflowEngine\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Template;
use OCP\Util;
use function class_exists;
use function function_exists;

class LoadAdditionalSettingsScriptsListener implements IEventListener {
	public function handle(Event $event): void {
		if (!function_exists('style')) {
			// This is hacky, but we need to load the template class
			class_exists(Template::class, true);
		}

		Util::addScript('core', 'files_fileinfo');
		Util::addScript('core', 'files_client');
		Util::addScript('core', 'systemtags');
		Util::addScript(Application::APP_ID, 'workflowengine');
	}
}

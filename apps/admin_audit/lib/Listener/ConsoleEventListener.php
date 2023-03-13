<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\Console\ConsoleEventV2;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements UserManagementEventListener<ConsoleEventV2>
 */
class ConsoleEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof ConsoleEventV2) {
			$this->runCommand($event);
		}
	}

	private function runCommand(ConsoleEventV2 $event): void {
		$arguments = $event->getArguments();
		if (!isset($arguments[1]) || $arguments[1] === '_completion') {
			// Don't log autocompletion
			return;
		}

		// Remove `./occ`
		array_shift($arguments);

		$this->log('Console command executed: %s',
			['arguments' => implode(' ', $arguments)],
			['arguments']
		);
	}
}

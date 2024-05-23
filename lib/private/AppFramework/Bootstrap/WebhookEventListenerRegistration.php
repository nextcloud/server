<?php

declare(strict_types=1);

/**
 * @copyright 2024 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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

namespace OC\AppFramework\Bootstrap;

use OC\EventDispatcher\WebhookCaller;
use OCP\EventDispatcher\Event;
use OCP\Server;

/**
 * @psalm-immutable
 */
class WebhookEventListenerRegistration extends ARegistration {
	public function __construct(
		string $appId,
		private string $event,
		private string $method,
		private string $uri,
		private array $options,
		private int $priority,
	) {
		parent::__construct($appId);
	}

	public function getEvent(): string {
		return $this->event;
	}

	public function getCallable(): callable {
		return function (Event $event) {
			Server::get(WebhookCaller::class)->callWebhook($event, $this->method, $this->uri, $this->options);
		};
	}

	public function getPriority(): int {
		return $this->priority;
	}
}

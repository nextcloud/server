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

namespace OC\EventDispatcher;

use OCP\EventDispatcher\Event;
use OCP\Http\Client\IClientService;

class WebhookCaller {
	public function __construct(
		private IClientService $clientService,
	) {
	}

	public function callWebhook(
		Event $event,
		string $method,
		string $uri,
		array $options,
	): void {
		$client = $this->clientService->newClient();
		$client->request($method, $uri, $options + ['query' => ['event' => $event::class]]);

		/**
		 * TODO:
		 * Serialization of the event
		 * Timeout or async
		 */
	}
}

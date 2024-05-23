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
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class WebhookCaller {
	public function __construct(
		private IClientService $clientService,
		private IUserSession $userSession,
		private LoggerInterface $logger,
	) {
	}

	public function callWebhook(
		Event $event,
		string $method,
		string $uri,
		array $options,
	): void {
		$client = $this->clientService->newClient();
		if (!isset($options['body'])) {
			$options['body'] = json_encode([
				'event' => $this->serializeEvent($event),
				'userid' => $this->userSession->getUser()?->getUID() ?? null,
			]);
		}
		try {
			$response = $client->request($method, $uri, $options + ['query' => ['event' => $event::class]]);
			$statusCode = $response->getStatusCode();
			if ($statusCode >= 200 && $statusCode < 300) {
				$this->logger->warning('Webhook returned unexpected status code '.$statusCode, ['body' => $response->getBody()]);
			} else {
				$this->logger->debug('Webhook returned status code '.$statusCode, ['body' => $response->getBody()]);
			}
		} catch (\Exception $e) {
			$this->logger->error('Webhook call failed: '.$e->getMessage(), ['exception' => $e]);
		}
	}

	private function serializeEvent(Event $event): array|\JsonSerializable {
		if ($event instanceof \JsonSerializable) {
			return $event;
		} else {
			/* Event is not serializable, we fallback to reflection to still send something */
			$data = [];
			$ref = new \ReflectionClass($event);
			foreach ($ref->getMethods() as $method) {
				if (str_starts_with($method->getName(), 'get')) {
					$key = strtolower(substr($method->getName(), 3));
					$value = $method->invoke($event);
					if ($value instanceof \OCP\Files\FileInfo) {
						$value = [
							'id' => $value->getId(),
							'path' => $value->getPath(),
						];
					}
					$data[$key] = $value;
				}
			}
			$this->logger->debug('Webhook had to use fallback to serialize event '.$event::class);
			return $data;
		}
	}
}

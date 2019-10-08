<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OCA\OAuth2\Service;

use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Exceptions\ClientNotFoundException;

class ClientService {
	/** @var ClientMapper */
	private $mapper;

	public function __construct(ClientMapper $mapper) {
		$this->mapper = $mapper;
	}

	/**
	 * @throws ClientNotFoundException
	 */
	public function getClient(string $clientId, string $clientSecret): Client {
		$client = $this->mapper->getByIdentifier($clientId);

		if ($client->getSecret() !== $clientSecret) {
			throw new ClientNotFoundException();
		}

		return $client;
	}
}

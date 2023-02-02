<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\OAuth2\Settings;

use OCA\OAuth2\Db\ClientMapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\ISettings;
use OCP\IURLGenerator;

class Admin implements ISettings {
	private IInitialState $initialState;
	private ClientMapper $clientMapper;
	private IURLGenerator $urlGenerator;

	public function __construct(
		IInitialState $initialState,
		ClientMapper $clientMapper,
		IURLGenerator $urlGenerator
	) {
		$this->initialState = $initialState;
		$this->clientMapper = $clientMapper;
		$this->urlGenerator = $urlGenerator;
	}

	public function getForm(): TemplateResponse {
		$clients = $this->clientMapper->getClients();
		$result = [];

		foreach ($clients as $client) {
			$result[] = [
				'id' => $client->getId(),
				'name' => $client->getName(),
				'redirectUri' => $client->getRedirectUri(),
				'clientId' => $client->getClientIdentifier(),
				'clientSecret' => $client->getSecret(),
			];
		}
		$this->initialState->provideInitialState('clients', $result);
		$this->initialState->provideInitialState('oauth2-doc-link', $this->urlGenerator->linkToDocs('admin-oauth2'));

		return new TemplateResponse(
			'oauth2',
			'admin',
			[],
			''
		);
	}

	public function getSection(): string {
		return 'security';
	}

	public function getPriority(): int {
		return 100;
	}
}

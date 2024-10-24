<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Settings;

use OCA\OAuth2\Db\ClientMapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
use Psr\Log\LoggerInterface;

class Admin implements ISettings {

	public function __construct(
		private IInitialState $initialState,
		private ClientMapper $clientMapper,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
	) {
	}

	public function getForm(): TemplateResponse {
		$clients = $this->clientMapper->getClients();
		$result = [];

		foreach ($clients as $client) {
			try {
				$result[] = [
					'id' => $client->getId(),
					'name' => $client->getName(),
					'redirectUri' => $client->getRedirectUri(),
					'clientId' => $client->getClientIdentifier(),
					'clientSecret' => '',
				];
			} catch (\Exception $e) {
				$this->logger->error('[Settings] OAuth client secret decryption error', ['exception' => $e]);
			}
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

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Share20;

use Exception;
use OC\AppFramework\Bootstrap\Coordinator;
use OCA\Files_Sharing\DefaultPublicShareTemplateProvider;
use OCP\Server;
use OCP\Share\IPublicShareTemplateFactory;
use OCP\Share\IPublicShareTemplateProvider;
use OCP\Share\IShare;

class PublicShareTemplateFactory implements IPublicShareTemplateFactory {
	public function __construct(
		private Coordinator $coordinator,
		private DefaultPublicShareTemplateProvider $defaultProvider,
	) {
	}

	public function getProvider(IShare $share): IPublicShareTemplateProvider {
		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			throw new Exception("Can't retrieve public share template providers as context is not defined");
		}

		$providers = array_map(
			fn ($registration) => Server::get($registration->getService()),
			$context->getPublicShareTemplateProviders()
		);

		$filteredProviders = array_filter(
			$providers,
			fn (IPublicShareTemplateProvider $provider) => $provider->shouldRespond($share)
		);

		if (count($filteredProviders) === 0) {
			return $this->defaultProvider;
		} else {
			return array_shift($filteredProviders);
		}
	}
}

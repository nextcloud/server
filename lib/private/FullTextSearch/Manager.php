<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\FullTextSearch;

use NCU\FullTextSearch\Exceptions\ServiceNotFoundException;
use NCU\FullTextSearch\IContentProvider;
use NCU\FullTextSearch\IManager;
use NCU\FullTextSearch\IService;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use Psr\Container\ContainerInterface;

class Manager implements IManager {
	private ?IService $service = null;

	public function __construct(
		private readonly Coordinator $coordinator,
		private readonly ContainerInterface $container,
	) {
	}

	/**
	 * @throws ServiceNotFoundException if no full text search service found
	 */
	public function getService(): IService {
		if ($this->service === null) {
			$registration = $this->coordinator->getRegistrationContext()?->getFullTextSearchService();
			if ($registration === null) {
				throw new ServiceNotFoundException('fts service not found');
			}
			$this->service = $this->container->get($registration->getService());
		}

		return $this->service;
	}

	/**
	 * @return ServiceRegistration<IContentProvider>[]
	 */
	public function getContentProviders(): array {
		return $this->coordinator->getRegistrationContext()?->getFullTextSearchContentProviders() ?? [];
	}
}

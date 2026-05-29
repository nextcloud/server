<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Fetcher;

use OC\Files\AppData\Factory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Support\Subscription\IRegistry;
use Psr\Log\LoggerInterface;

/**
 * Fetch categories from the app store server.
 * The categories are listed as an array containing the id and the translations for the category.
 * The key of the translations array is the language code and the value is an array containing the name and description of the category.
 *
 * @psalm-import-type AppStoreFetcherCategory from ResponseDefinitions
 * @template-extends Fetcher<AppStoreFetcherCategory>
 */
class CategoryFetcher extends Fetcher {
	public function __construct(
		Factory $appDataFactory,
		IClientService $clientService,
		ITimeFactory $timeFactory,
		IConfig $config,
		LoggerInterface $logger,
		IRegistry $registry,
	) {
		parent::__construct(
			$appDataFactory,
			$clientService,
			$timeFactory,
			$config,
			$logger,
			$registry
		);

		$this->fileName = 'categories.json';
		$this->endpointName = 'categories.json';
	}
}

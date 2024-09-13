<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\SetupCheck;

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\CheckServerResponseTrait;
use Psr\Log\LoggerInterface;

/**
 * Dummy implementation for CheckServerResponseTraitTest
 */
class CheckServerResponseTraitImplementation {

	use CheckServerResponseTrait {
		CheckServerResponseTrait::getRequestOptions as public;
		CheckServerResponseTrait::runRequest as public;
		CheckServerResponseTrait::normalizeUrl as public;
		CheckServerResponseTrait::getTestUrls as public;
	}

	public function __construct(
		protected IL10N $l10n,
		protected IConfig $config,
		protected IURLGenerator $urlGenerator,
		protected IClientService $clientService,
		protected LoggerInterface $logger,
	) {
	}

}

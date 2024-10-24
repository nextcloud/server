<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\CheckServerResponseTrait;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;

/**
 * Checks if the webserver serves '.map' files using the correct MIME type
 */
class JavaScriptSourceMaps implements ISetupCheck {
	use CheckServerResponseTrait;

	public function __construct(
		protected IL10N $l10n,
		protected IConfig $config,
		protected IURLGenerator $urlGenerator,
		protected IClientService $clientService,
		protected LoggerInterface $logger,
	) {
	}

	public function getCategory(): string {
		return 'network';
	}

	public function getName(): string {
		return $this->l10n->t('JavaScript source map support');
	}

	public function run(): SetupResult {
		$testFile = $this->urlGenerator->linkTo('settings', 'js/map-test.js.map');

		foreach ($this->runRequest('HEAD', $testFile) as $response) {
			return SetupResult::success();
		}

		return SetupResult::warning($this->l10n->t('Your webserver is not set up to serve `.js.map` files. Without these files, JavaScript Source Maps won\'t function properly, making it more challenging to troubleshoot and debug any issues that may arise.'));
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
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
 * Checks if the webserver serves '.mjs' files using the correct MIME type
 */
class JavaScriptModules implements ISetupCheck {
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
		return $this->l10n->t('JavaScript modules support');
	}

	public function run(): SetupResult {
		$testFile = $this->urlGenerator->linkTo('settings', 'js/esm-test.mjs');

		$noResponse = true;
		foreach ($this->runRequest('HEAD', $testFile) as $response) {
			$noResponse = false;
			if (preg_match('/(text|application)\/javascript/i', $response->getHeader('Content-Type'))) {
				return SetupResult::success();
			}
		}

		if ($noResponse) {
			return SetupResult::warning($this->l10n->t('Unable to run check for JavaScript support. Please remedy or confirm manually if your webserver serves `.mjs` files using the JavaScript MIME type.') . "\n" . $this->serverConfigHelp());
		}
		return SetupResult::error($this->l10n->t('Your webserver does not serve `.mjs` files using the JavaScript MIME type. This will break some apps by preventing browsers from executing the JavaScript files. You should configure your webserver to serve `.mjs` files with either the `text/javascript` or `application/javascript` MIME type.'));

	}
}

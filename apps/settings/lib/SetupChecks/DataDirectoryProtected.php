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
 * Checks if the data directory can not be accessed from outside
 */
class DataDirectoryProtected implements ISetupCheck {
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
		return $this->l10n->t('Data directory protected');
	}

	public function run(): SetupResult {
		$dataDir = str_replace(\OC::$SERVERROOT . '/', '', $this->config->getSystemValueString('datadirectory', ''));
		$dataUrl = $this->urlGenerator->linkTo('', $dataDir . '/.ncdata');

		$noResponse = true;
		foreach ($this->runRequest('GET', $dataUrl, [ 'httpErrors' => false ]) as $response) {
			$noResponse = false;
			if ($response->getStatusCode() < 400) {
				// Read the response body
				$body = $response->getBody();
				if (is_resource($body)) {
					$body = stream_get_contents($body, 64);
				}

				if (str_contains($body, '# Nextcloud data directory')) {
					return SetupResult::error($this->l10n->t('Your data directory and files are probably accessible from the internet. The .htaccess file is not working. It is strongly recommended that you configure your web server so that the data directory is no longer accessible, or move the data directory outside the web server document root.'));
				}
			} else {
				$this->logger->debug('[expected] Could not access data directory from outside.', ['url' => $dataUrl]);
			}
		}

		if ($noResponse) {
			return SetupResult::warning($this->l10n->t('Could not check that the data directory is protected. Please check manually that your server does not allow access to the data directory.') . "\n" . $this->serverConfigHelp());
		}
		return SetupResult::success();
		
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use GuzzleHttp\Psr7;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\CheckServerResponseTrait;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;

class RequestBuffering implements ISetupCheck {

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
		return $this->l10n->t('Request buffering');
	}

	public function run(): SetupResult {
		$usingFPM = function_exists('fastcgi_finish_request');
		if (!$usingFPM && !\OC::$CLI) {
			return SetupResult::success(
				$this->l10n->t('Not using PHP-FPM.')
			);
		}

		$works = null;
		$stream = Psr7\Utils::streamFor(str_repeat('x', 1337));
		$options = [
			'body' => $stream,
			'headers' => [
				'Transfer-Encoding' => 'chunked',
			],
		];
		$url = $this->urlGenerator->linkToRoute('settings.CheckSetup.checkContentLengthHeader');
		foreach ($this->runRequest('PUT', $url, ['ignoreSSL' => true, 'options' => $options]) as $response) {
			$contentType = $response->getHeader('Content-Type');
			if (!str_contains(strtolower($contentType), 'application/json')) {
				continue;
			}

			$body = $response->getBody();
			$body = is_resource($body) ? stream_get_contents($body) : $body;
			$works = $works || $body === '"1337"';
		}

		if ($works === null) {
			return SetupResult::info(
				$this->l10n->t('Could not check that your web server has configured request buffering. Please check manually.'),
			);
		} elseif ($works === true) {
			return SetupResult::success(
				$this->l10n->t('Your web server seems to have request buffering configured correctly.'),
			);
		} else {
			if ($usingFPM) {
				return SetupResult::error(
					$this->l10n->t('Your web server is not configured for request buffering, this will cause broken uploads with some clients.')
					. ' '
					. $this->l10n->t('Due to a limitation of PHP-FPM chunked requests will not be passed to Nextcloud if the server does not buffer such requests.'),
				);
			} else {
				// Not using FPM but we are on CLI so we do not know if FPM is used
				return SetupResult::warning(
					$this->l10n->t('Your web server is not configured for request buffering, if you are running PHP-FPM this will cause broken uploads with some clients.')
					. ' '
					. $this->l10n->t('Due to a limitation of PHP-FPM chunked requests will not be passed to Nextcloud if the server does not buffer such requests.'),
				);
			}
		}
	}
}

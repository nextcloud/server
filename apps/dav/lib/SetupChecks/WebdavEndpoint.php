<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\SetupChecks;

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\CheckServerResponseTrait;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;

class WebdavEndpoint implements ISetupCheck {

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
		return $this->l10n->t('WebDAV endpoint');
	}

	public function run(): SetupResult {
		$urls = [
			['propfind', '/remote.php/webdav', [207, 401]],
		];

		foreach ($urls as [$verb,$url,$validStatuses]) {
			$works = null;
			foreach ($this->runRequest($verb, $url, ['httpErrors' => false]) as $response) {
				// Check that the response status matches
				$works = in_array($response->getStatusCode(), $validStatuses);
				// Skip the other requests if one works
				if ($works === true) {
					break;
				}
			}
			// If 'works' is null then we could not connect to the server
			if ($works === null) {
				return SetupResult::info(
					$this->l10n->t('Could not check that your web server is properly set up to allow file synchronization over WebDAV. Please check manually.') . "\n" . $this->serverConfigHelp(),
					$this->urlGenerator->linkToDocs('admin-setup-well-known-URL'),
				);
			}
			// Otherwise if we fail we can abort here
			if ($works === false) {
				return SetupResult::error(
					$this->l10n->t('Your web server is not yet properly set up to allow file synchronization, because the WebDAV interface seems to be broken.') . "\n" . $this->serverConfigHelp(),
				);
			}
		}
		return SetupResult::success(
			$this->l10n->t('Your web server is properly set up to allow file synchronization over WebDAV.')
		);
	}
}

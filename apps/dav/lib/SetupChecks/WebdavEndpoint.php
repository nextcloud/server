<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\SetupChecks;

use OCA\Settings\SetupChecks\CheckServerResponseTrait;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
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

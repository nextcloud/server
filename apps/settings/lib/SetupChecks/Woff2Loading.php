<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
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
namespace OCA\Settings\SetupChecks;

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;

/**
 * Check whether the WOFF2 URLs works
 */
class Woff2Loading implements ISetupCheck {
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
		return $this->l10n->t('WOFF2 file loading');
	}

	public function run(): SetupResult {
		$url = $this->urlGenerator->linkTo('', 'core/fonts/NotoSans-Regular-latin.woff2');
		$noResponse = true;
		$responses = $this->runHEAD($url);
		foreach ($responses as $response) {
			$noResponse = false;
			if ($response->getStatusCode() === 200) {
				return SetupResult::success();
			}
		}

		if ($noResponse) {
			return SetupResult::info(
				$this->l10n->t('Could not check for WOFF2 loading support. Please check manually if your webserver serves `.woff2` files.') . "\n" . $this->serverConfigHelp(),
				$this->urlGenerator->linkToDocs('admin-nginx'),
			);
		}
		return SetupResult::warning(
			$this->l10n->t('Your web server is not properly set up to deliver .woff2 files. This is typically an issue with the Nginx configuration. For Nextcloud 15 it needs an adjustement to also deliver .woff2 files. Compare your Nginx configuration to the recommended configuration in our documentation.'),
			$this->urlGenerator->linkToDocs('admin-nginx'),
		);
		
	}
}

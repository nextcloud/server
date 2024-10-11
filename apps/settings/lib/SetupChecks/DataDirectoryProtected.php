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
		$dataUrl = $this->urlGenerator->linkTo('', $dataDir . '/.ocdata');

		$noResponse = true;
		foreach ($this->runHEAD($dataUrl, httpErrors:false) as $response) {
			$noResponse = false;
			if ($response->getStatusCode() === 200) {
				return SetupResult::error($this->l10n->t('Your data directory and files are probably accessible from the internet. The .htaccess file is not working. It is strongly recommended that you configure your web server so that the data directory is no longer accessible, or move the data directory outside the web server document root.'));
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

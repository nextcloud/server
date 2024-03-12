<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

		foreach ($this->runHEAD($testFile) as $response) {
			return SetupResult::success();
		}

		return SetupResult::warning($this->l10n->t('Your webserver is not set up to serve `.js.map` files. Without these files, JavaScript Source Maps won\'t function properly, making it more challenging to troubleshoot and debug any issues that may arise.'));
	}
}

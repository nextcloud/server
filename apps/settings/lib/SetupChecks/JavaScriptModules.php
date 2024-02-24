<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
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
		foreach ($this->runHEAD($testFile) as $response) {
			$noResponse = false;
			if (preg_match('/(text|application)\/javascript/i', $response->getHeader('Content-Type'))) {
				return SetupResult::success();
			}
		}

		if ($noResponse) {
			return SetupResult::warning($this->l10n->t('Could not check for JavaScript support. Please check manually if your webserver serves `.mjs` files using the JavaScript MIME type.') . "\n" . $this->serverConfigHelp());
		}
		return SetupResult::error($this->l10n->t('Your webserver does not serve `.mjs` files using the JavaScript MIME type. This will break some apps by preventing browsers from executing the JavaScript files. You should configure your webserver to serve `.mjs` files with either the `text/javascript` or `application/javascript` MIME type.'));
		
	}
}

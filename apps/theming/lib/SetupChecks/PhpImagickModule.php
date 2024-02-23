<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
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
namespace OCA\Theming\SetupChecks;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpImagickModule implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('PHP Imagick module');
	}

	public function getCategory(): string {
		return 'php';
	}

	public function run(): SetupResult {
		if (!extension_loaded('imagick')) {
			return SetupResult::info(
				$this->l10n->t('The PHP module "imagick" is not enabled although the theming app is. For favicon generation to work correctly, you need to install and enable this module.'),
				$this->urlGenerator->linkToDocs('admin-php-modules')
			);
		} elseif (count(\Imagick::queryFormats('SVG')) === 0) {
			return SetupResult::info(
				$this->l10n->t('The PHP module "imagick" in this instance has no SVG support. For better compatibility it is recommended to install it.'),
				$this->urlGenerator->linkToDocs('admin-php-modules')
			);
		} else {
			return SetupResult::success();
		}
	}
}

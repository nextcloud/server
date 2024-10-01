<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

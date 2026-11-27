<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\SetupChecks;

use OCA\Theming\ImageManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpImagickModule implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ImageManager $imageManager,
	) {
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('PHP Imagick module');
	}

	#[\Override]
	public function getCategory(): string {
		return 'php';
	}

	#[\Override]
	public function run(): SetupResult {
		if ($this->imageManager->canConvert('SVG') && $this->imageManager->canConvert('PNG')) {
			return SetupResult::success();
		}

		$issues = [];
		// an uploaded favicon is used as touch icon as-is
		if (!$this->imageManager->hasImage('favicon')) {
			$issues[] = $this->l10n->t('Icons for the home screen of mobile devices and for "Add to Dock" in Safari cannot be themed and show the default icon instead. Upload a PNG favicon or install the module with SVG support to avoid this.');
		}
		$logoMime = $this->imageManager->getImageMime('logo');
		if ($logoMime === 'image/svg+xml' || $logoMime === 'image/svg') {
			$issues[] = $this->l10n->t('The custom logo was uploaded as SVG and cannot be converted to PNG, so it will be missing in emails for many mail clients (e.g. Gmail and Outlook) that do not display SVG images. Upload the logo as PNG or install the module with SVG support to avoid this.');
		}
		if ($issues === []) {
			return SetupResult::success();
		}

		return SetupResult::info(
			$this->l10n->t('The PHP module "imagick" is not enabled or has no SVG support.') . ' ' . implode(' ', $issues),
			$this->urlGenerator->linkToDocs('admin-php-modules')
		);
	}
}

<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpModules implements ISetupCheck {
	protected const REQUIRED_MODULES = [
		'ctype',
		'curl',
		'dom',
		'fileinfo',
		'gd',
		'json',
		'mbstring',
		'openssl',
		'posix',
		'session',
		'xml',
		'xmlreader',
		'xmlwriter',
		'zip',
		'zlib',
	];
	protected const RECOMMENDED_MODULES = [
		'exif',
		'gmp',
		'intl',
		'sodium',
		'sysvsem',
	];

	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('PHP modules');
	}

	public function getCategory(): string {
		return 'php';
	}

	protected function getRecommendedModuleDescription(string $module): string {
		return match($module) {
			'intl' => $this->l10n->t('increases language translation performance and fixes sorting of non-ASCII characters'),
			'sodium' => $this->l10n->t('for Argon2 for password hashing'),
			'gmp' => $this->l10n->t('required for SFTP storage and recommended for WebAuthn performance'),
			'exif' => $this->l10n->t('for picture rotation in server and metadata extraction in the Photos app'),
			default => '',
		};
	}

	public function run(): SetupResult {
		$missingRecommendedModules = $this->getMissingModules(self::RECOMMENDED_MODULES);
		$missingRequiredModules = $this->getMissingModules(self::REQUIRED_MODULES);
		if (!empty($missingRequiredModules)) {
			return SetupResult::error(
				$this->l10n->t('This instance is missing some required PHP modules. It is required to install them: %s.', implode(', ', $missingRequiredModules)),
				$this->urlGenerator->linkToDocs('admin-php-modules')
			);
		} elseif (!empty($missingRecommendedModules)) {
			$moduleList = implode(
				"\n",
				array_map(
					fn (string $module) => '- ' . $module . ' ' . $this->getRecommendedModuleDescription($module),
					$missingRecommendedModules
				)
			);
			return SetupResult::info(
				$this->l10n->t("This instance is missing some recommended PHP modules. For improved performance and better compatibility it is highly recommended to install them:\n%s", $moduleList),
				$this->urlGenerator->linkToDocs('admin-php-modules')
			);
		} else {
			return SetupResult::success();
		}
	}

	/**
	 * Checks for potential PHP modules that would improve the instance
	 *
	 * @param string[] $modules modules to test
	 * @return string[] A list of PHP modules which are missing
	 */
	protected function getMissingModules(array $modules): array {
		return array_values(array_filter(
			$modules,
			fn (string $module) => !extension_loaded($module),
		));
	}
}

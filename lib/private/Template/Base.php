<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Template;

use OCP\Defaults;

class Base {
	private $template; // The template
	private array $vars = [];

	/** @var \OCP\IL10N */
	private $l10n;

	/** @var Defaults */
	private $theme;

	/**
	 * @param string $template
	 * @param string $requestToken
	 * @param \OCP\IL10N $l10n
	 * @param string $cspNonce
	 * @param Defaults $theme
	 */
	public function __construct($template, $requestToken, $l10n, $theme, $cspNonce) {
		$this->vars = [
			'cspNonce' => $cspNonce,
			'requesttoken' => $requestToken,
		];
		$this->l10n = $l10n;
		$this->template = $template;
		$this->theme = $theme;
	}

	/**
	 * @param string $serverRoot
	 * @param string|false $app_dir
	 * @param string $theme
	 * @param string $app
	 * @return string[]
	 */
	protected function getAppTemplateDirs($theme, $app, $serverRoot, $app_dir) {
		// Check if the app is in the app folder or in the root
		if ($app_dir !== false && file_exists($app_dir . '/templates/')) {
			return [
				$serverRoot . '/themes/' . $theme . '/apps/' . $app . '/templates/',
				$app_dir . '/templates/',
			];
		}
		return [
			$serverRoot . '/themes/' . $theme . '/' . $app . '/templates/',
			$serverRoot . '/' . $app . '/templates/',
		];
	}

	/**
	 * @return string[]
	 */
	protected function getCoreTemplateDirs(string $theme, string $serverRoot): array {
		return [
			$serverRoot . '/themes/' . $theme . '/core/templates/',
			$serverRoot . '/core/templates/',
		];
	}

	/**
	 * Assign variables
	 *
	 * This function assigns a variable. It can be accessed via $_[$key] in
	 * the template.
	 *
	 * If the key existed before, it will be overwritten
	 */
	public function assign(string $key, mixed $value): void {
		$this->vars[$key] = $value;
	}

	/**
	 * Appends a variable
	 *
	 * This function assigns a variable in an array context. If the key already
	 * exists, the value will be appended. It can be accessed via
	 * $_[$key][$position] in the template.
	 */
	public function append(string $key, mixed $value): void {
		if (array_key_exists($key, $this->vars)) {
			$this->vars[$key][] = $value;
		} else {
			$this->vars[$key] = [ $value ];
		}
	}

	/**
	 * Prints the proceeded template
	 *
	 * This function proceeds the template and prints its output.
	 */
	public function printPage(): void {
		$data = $this->fetchPage();
		print $data;
	}

	/**
	 * Process the template
	 *
	 * This function processes the template.
	 */
	public function fetchPage(?array $additionalParams = null): string {
		return $this->load($this->template, $additionalParams);
	}

	/**
	 * doing the actual work
	 *
	 * Includes the template file, fetches its output
	 */
	protected function load(string $file, ?array $additionalParams = null): string {
		// Register the variables
		$_ = $this->vars;
		$l = $this->l10n;
		$theme = $this->theme;

		if (!is_null($additionalParams)) {
			$_ = array_merge($additionalParams, $this->vars);
			foreach ($_ as $var => $value) {
				if (!isset(${$var})) {
					${$var} = $value;
				}
			}
		}

		// Include
		ob_start();
		try {
			require_once __DIR__ . '/functions.php';
			include $file;
			$data = ob_get_contents();
		} catch (\Exception $e) {
			@ob_end_clean();
			throw $e;
		}
		@ob_end_clean();

		// Return data
		return $data;
	}
}

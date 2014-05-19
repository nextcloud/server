<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Template;

class Base {
	private $template; // The template
	private $vars; // Vars
	private $l10n; // The l10n-Object
	private $theme; // theme defaults

	/**
	 * @param string $template
	 * @param \OC_L10N $l10n
	 * @param \OC_Defaults $theme
	 */
	public function __construct( $template, $requesttoken, $l10n, $theme ) {
		$this->vars = array();
		$this->vars['requesttoken'] = $requesttoken;
		$this->l10n = $l10n;
		$this->template = $template;
		$this->theme = $theme;
	}

	/**
	 * @param string $serverroot
	 * @param string|false $app_dir
	 * @param string $theme
	 * @param string $app
	 */
	protected function getAppTemplateDirs($theme, $app, $serverroot, $app_dir) {
		// Check if the app is in the app folder or in the root
		if( file_exists($app_dir.'/templates/' )) {
			return array(
				$serverroot.'/themes/'.$theme.'/apps/'.$app.'/templates/',
				$app_dir.'/templates/',
			);
		}
		return array(
			$serverroot.'/themes/'.$theme.'/'.$app.'/templates/',
			$serverroot.'/'.$app.'/templates/',
		);
	}

	/**
	 * @param string $serverroot
	 * @param string $theme
	 */
	protected function getCoreTemplateDirs($theme, $serverroot) {
		return array(
			$serverroot.'/themes/'.$theme.'/core/templates/',
			$serverroot.'/core/templates/',
		);
	}

	/**
	 * Assign variables
	 * @param string $key key
	 * @param array|bool|integer|string $value value
	 * @return bool
	 *
	 * This function assigns a variable. It can be accessed via $_[$key] in
	 * the template.
	 *
	 * If the key existed before, it will be overwritten
	 */
	public function assign( $key, $value) {
		$this->vars[$key] = $value;
		return true;
	}

	/**
	 * Appends a variable
	 * @param string $key key
	 * @param mixed $value value
	 * @return boolean|null
	 *
	 * This function assigns a variable in an array context. If the key already
	 * exists, the value will be appended. It can be accessed via
	 * $_[$key][$position] in the template.
	 */
	public function append( $key, $value ) {
		if( array_key_exists( $key, $this->vars )) {
			$this->vars[$key][] = $value;
		}
		else{
			$this->vars[$key] = array( $value );
		}
	}

	/**
	 * Prints the proceeded template
	 * @return bool
	 *
	 * This function proceeds the template and prints its output.
	 */
	public function printPage() {
		$data = $this->fetchPage();
		if( $data === false ) {
			return false;
		}
		else{
			print $data;
			return true;
		}
	}

	/**
	 * Process the template
	 * @return string
	 *
	 * This function processes the template.
	 */
	public function fetchPage() {
		return $this->load($this->template);
	}

	/**
	 * doing the actual work
	 * @param string $file
	 * @return string content
	 *
	 * Includes the template file, fetches its output
	 */
	protected function load( $file, $additionalparams = null ) {
		// Register the variables
		$_ = $this->vars;
		$l = $this->l10n;
		$theme = $this->theme;

		if( !is_null($additionalparams)) {
			$_ = array_merge( $additionalparams, $this->vars );
		}

		// Include
		ob_start();
		include $file;
		$data = ob_get_contents();
		@ob_end_clean();

		// Return data
		return $data;
	}

}

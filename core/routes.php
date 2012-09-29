<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('settings/routes.php');

// Not specifically routed
$this->create('app_css', '/apps/{app}/{file}')
	->requirements(array('file' => '.*.css'))
	->action('OC', 'loadCSSFile');
$this->create('app_index_script', '/apps/{app}/')
	->defaults(array('file' => 'index.php'))
	//->requirements(array('file' => '.*.php'))
	->action('OC', 'loadAppScriptFile');
$this->create('app_script', '/apps/{app}/{file}')
	->defaults(array('file' => 'index.php'))
	->requirements(array('file' => '.*.php'))
	->action('OC', 'loadAppScriptFile');

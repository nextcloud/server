<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Post installation check
$this->create('post_setup_check', '/post-setup-check')
	->action('OC_Setup', 'postSetupCheck');

// Core ajax actions
// Search
$this->create('search_ajax_search', '/search/ajax/search.php')
	->actionInclude('search/ajax/search.php');
// AppConfig
$this->create('core_ajax_appconfig', '/core/ajax/appconfig.php')
	->actionInclude('core/ajax/appconfig.php');
// Share
$this->create('core_ajax_share', '/core/ajax/share.php')
	->actionInclude('core/ajax/share.php');
// Translations
$this->create('core_ajax_translations', '/core/ajax/translations.php')
	->actionInclude('core/ajax/translations.php');
// VCategories
$this->create('core_ajax_vcategories_add', '/core/ajax/vcategories/add.php')
	->actionInclude('core/ajax/vcategories/add.php');
$this->create('core_ajax_vcategories_delete', '/core/ajax/vcategories/delete.php')
	->actionInclude('core/ajax/vcategories/delete.php');
$this->create('core_ajax_vcategories_addtofavorites', '/core/ajax/vcategories/addToFavorites.php')
	->actionInclude('core/ajax/vcategories/addToFavorites.php');
$this->create('core_ajax_vcategories_removefromfavorites', '/core/ajax/vcategories/removeFromFavorites.php')
	->actionInclude('core/ajax/vcategories/removeFromFavorites.php');
$this->create('core_ajax_vcategories_favorites', '/core/ajax/vcategories/favorites.php')
	->actionInclude('core/ajax/vcategories/favorites.php');
$this->create('core_ajax_vcategories_edit', '/core/ajax/vcategories/edit.php')
	->actionInclude('core/ajax/vcategories/edit.php');
// oC JS config
$this->create('js_config', '/core/js/config.js')
	->actionInclude('core/js/config.php');
// Routing
$this->create('core_ajax_routes', '/core/routes.json')
	->action('OC_Router', 'JSRoutes');

OC::$CLASSPATH['OC_Core_LostPassword_Controller'] = 'core/lostpassword/controller.php';
$this->create('core_lostpassword_index', '/lostpassword/')
	->get()
	->action('OC_Core_LostPassword_Controller', 'index');
$this->create('core_lostpassword_send_email', '/lostpassword/')
	->post()
	->action('OC_Core_LostPassword_Controller', 'sendEmail');
$this->create('core_lostpassword_reset', '/lostpassword/reset/{token}/{user}')
	->get()
	->action('OC_Core_LostPassword_Controller', 'reset');
$this->create('core_lostpassword_reset_password', '/lostpassword/reset/{token}/{user}')
	->post()
	->action('OC_Core_LostPassword_Controller', 'resetPassword');

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

// used for heartbeat
$this->create('heartbeat', '/heartbeat')->action(function(){
	// do nothing
});
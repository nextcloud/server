<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OC\Core\Application;

$application = new Application();
$application->registerRoutes($this, array('routes' => array(
		array('name' => 'lost#email', 'url' => '/lostpassword/email', 'verb' => 'POST'),
		array('name' => 'lost#resetform', 'url' => '/lostpassword/reset/form/{token}/{userId}', 'verb' => 'GET'),
		array('name' => 'lost#setPassword', 'url' => '/lostpassword/set/{token}/{userId}', 'verb' => 'POST'),
		array('name' => 'user#getDisplayNames', 'url' => '/displaynames', 'verb' => 'POST'),
	)
));

// Post installation check

/** @var $this OCP\Route\IRouter */
// Core ajax actions
// Search
$this->create('search_ajax_search', '/core/search')
	->actionInclude('core/search/ajax/search.php');
// AppConfig
$this->create('core_ajax_appconfig', '/core/ajax/appconfig.php')
	->actionInclude('core/ajax/appconfig.php');
// Share
$this->create('core_ajax_share', '/core/ajax/share.php')
	->actionInclude('core/ajax/share.php');
// Tags
$this->create('core_tags_tags', '/tags/{type}')
	->get()
	->action('OC\Core\Tags\Controller', 'getTags')
	->requirements(array('type'));
$this->create('core_tags_favorites', '/tags/{type}/favorites')
	->get()
	->action('OC\Core\Tags\Controller', 'getFavorites')
	->requirements(array('type'));
$this->create('core_tags_ids_for_tag', '/tags/{type}/ids')
	->get()
	->action('OC\Core\Tags\Controller', 'getIdsForTag')
	->requirements(array('type'));
$this->create('core_tags_favorite', '/tags/{type}/favorite/{id}/')
	->post()
	->action('OC\Core\Tags\Controller', 'favorite')
	->requirements(array('type', 'id'));
$this->create('core_tags_unfavorite', '/tags/{type}/unfavorite/{id}/')
	->post()
	->action('OC\Core\Tags\Controller', 'unFavorite')
	->requirements(array('type', 'id'));
$this->create('core_tags_tag', '/tags/{type}/tag/{id}/')
	->post()
	->action('OC\Core\Tags\Controller', 'tagAs')
	->requirements(array('type', 'id'));
$this->create('core_tags_untag', '/tags/{type}/untag/{id}/')
	->post()
	->action('OC\Core\Tags\Controller', 'unTag')
	->requirements(array('type', 'id'));
$this->create('core_tags_add', '/tags/{type}/add')
	->post()
	->action('OC\Core\Tags\Controller', 'addTag')
	->requirements(array('type'));
$this->create('core_tags_delete', '/tags/{type}/delete')
	->post()
	->action('OC\Core\Tags\Controller', 'deleteTags')
	->requirements(array('type'));
// oC JS config
$this->create('js_config', '/core/js/oc.js')
	->actionInclude('core/js/config.php');
// Routing
$this->create('core_ajax_preview', '/core/preview')
	->actionInclude('core/ajax/preview.php');
$this->create('core_ajax_preview', '/core/preview.png')
	->actionInclude('core/ajax/preview.php');
$this->create('core_ajax_update', '/core/ajax/update.php')
	->actionInclude('core/ajax/update.php');
// Avatar routes
$this->create('core_avatar_get_tmp', '/avatar/tmp')
	->get()
	->action('OC\Core\Avatar\Controller', 'getTmpAvatar');
$this->create('core_avatar_get', '/avatar/{user}/{size}')
	->get()
	->action('OC\Core\Avatar\Controller', 'getAvatar');
$this->create('core_avatar_post', '/avatar/')
	->post()
	->action('OC\Core\Avatar\Controller', 'postAvatar');
$this->create('core_avatar_delete', '/avatar/')
	->delete()
	->action('OC\Core\Avatar\Controller', 'deleteAvatar');
$this->create('core_avatar_post_cropped', '/avatar/cropped')
	->post()
	->action('OC\Core\Avatar\Controller', 'postCroppedAvatar');

// Sharing routes
$this->create('files_sharing.sharecontroller.showShare', '/s/{token}')->action(function($urlParams) {
	$app = new \OCA\Files_Sharing\Application($urlParams);
	$app->dispatch('ShareController', 'showShare');
});
$this->create('files_sharing.sharecontroller.authenticate', '/s/{token}/authenticate')->post()->action(function($urlParams) {
	$app = new \OCA\Files_Sharing\Application($urlParams);
	$app->dispatch('ShareController', 'authenticate');
});
$this->create('files_sharing.sharecontroller.showAuthenticate', '/s/{token}/authenticate')->get()->action(function($urlParams) {
	$app = new \OCA\Files_Sharing\Application($urlParams);
	$app->dispatch('ShareController', 'showAuthenticate');
});
$this->create('files_sharing.sharecontroller.downloadShare', '/s/{token}/download')->get()->action(function($urlParams) {
	$app = new \OCA\Files_Sharing\Application($urlParams);
	$app->dispatch('ShareController', 'downloadShare');
});

// used for heartbeat
$this->create('heartbeat', '/heartbeat')->action(function(){
	// do nothing
});

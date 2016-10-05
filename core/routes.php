<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

use OC\Core\Application;

$application = new Application();
$application->registerRoutes($this, [
	'routes' => [
		['name' => 'lost#email', 'url' => '/lostpassword/email', 'verb' => 'POST'],
		['name' => 'lost#resetform', 'url' => '/lostpassword/reset/form/{token}/{userId}', 'verb' => 'GET'],
		['name' => 'lost#setPassword', 'url' => '/lostpassword/set/{token}/{userId}', 'verb' => 'POST'],
		['name' => 'user#getDisplayNames', 'url' => '/displaynames', 'verb' => 'POST'],
		['name' => 'avatar#getAvatar', 'url' => '/avatar/{userId}/{size}', 'verb' => 'GET'],
		['name' => 'avatar#deleteAvatar', 'url' => '/avatar/', 'verb' => 'DELETE'],
		['name' => 'avatar#postCroppedAvatar', 'url' => '/avatar/cropped', 'verb' => 'POST'],
		['name' => 'avatar#getTmpAvatar', 'url' => '/avatar/tmp', 'verb' => 'GET'],
		['name' => 'avatar#postAvatar', 'url' => '/avatar/', 'verb' => 'POST'],
		['name' => 'login#tryLogin', 'url' => '/login', 'verb' => 'POST'],
		['name' => 'login#showLoginForm', 'url' => '/login', 'verb' => 'GET'],
		['name' => 'login#logout', 'url' => '/logout', 'verb' => 'GET'],
		['name' => 'token#generateToken', 'url' => '/token/generate', 'verb' => 'POST'],
		['name' => 'OC\Core\Controller\Occ#execute', 'url' => '/occ/{command}', 'verb' => 'POST'],
		['name' => 'TwoFactorChallenge#selectChallenge', 'url' => '/login/selectchallenge', 'verb' => 'GET'],
		['name' => 'TwoFactorChallenge#showChallenge', 'url' => '/login/challenge/{challengeProviderId}', 'verb' => 'GET'],
		['name' => 'TwoFactorChallenge#solveChallenge', 'url' => '/login/challenge/{challengeProviderId}', 'verb' => 'POST'],
	],
]);

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

// File routes
$this->create('files.viewcontroller.showFile', '/f/{fileId}')->action(function($urlParams) {
	$app = new \OCA\Files\AppInfo\Application($urlParams);
	$app->dispatch('ViewController', 'showFile');
});

// Sharing routes
$this->create('files_sharing.sharecontroller.showShare', '/s/{token}')->action(function($urlParams) {
	$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
	$app->dispatch('ShareController', 'showShare');
});
$this->create('files_sharing.sharecontroller.authenticate', '/s/{token}/authenticate')->post()->action(function($urlParams) {
	$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
	$app->dispatch('ShareController', 'authenticate');
});
$this->create('files_sharing.sharecontroller.showAuthenticate', '/s/{token}/authenticate')->get()->action(function($urlParams) {
	$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
	$app->dispatch('ShareController', 'showAuthenticate');
});
$this->create('files_sharing.sharecontroller.downloadShare', '/s/{token}/download')->get()->action(function($urlParams) {
	$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
	$app->dispatch('ShareController', 'downloadShare');
});

// used for heartbeat
$this->create('heartbeat', '/heartbeat')->action(function(){
	// do nothing
});

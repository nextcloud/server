<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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
		['name' => 'login#confirmPassword', 'url' => '/login/confirm', 'verb' => 'POST'],
		['name' => 'login#showLoginForm', 'url' => '/login', 'verb' => 'GET'],
		['name' => 'login#logout', 'url' => '/logout', 'verb' => 'GET'],
		['name' => 'ClientFlowLogin#showAuthPickerPage', 'url' => '/login/flow', 'verb' => 'GET'],
		['name' => 'ClientFlowLogin#redirectPage', 'url' => '/login/flow/redirect', 'verb' => 'GET'],
		['name' => 'ClientFlowLogin#generateAppPassword', 'url' => '/login/flow', 'verb' => 'POST'],
		['name' => 'TwoFactorChallenge#selectChallenge', 'url' => '/login/selectchallenge', 'verb' => 'GET'],
		['name' => 'TwoFactorChallenge#showChallenge', 'url' => '/login/challenge/{challengeProviderId}', 'verb' => 'GET'],
		['name' => 'TwoFactorChallenge#solveChallenge', 'url' => '/login/challenge/{challengeProviderId}', 'verb' => 'POST'],
		['name' => 'OCJS#getConfig', 'url' => '/core/js/oc.js', 'verb' => 'GET'],
		['name' => 'Preview#getPreview', 'url' => '/core/preview', 'verb' => 'GET'],
		['name' => 'Preview#getPreview', 'url' => '/core/preview.png', 'verb' => 'GET'],
		['name' => 'Css#getCss', 'url' => '/css/{appName}/{fileName}', 'verb' => 'GET'],
		['name' => 'Js#getJs', 'url' => '/js/{appName}/{fileName}', 'verb' => 'GET'],
		['name' => 'contactsMenu#index', 'url' => '/contactsmenu/contacts', 'verb' => 'POST'],
		['name' => 'contactsMenu#findOne', 'url' => '/contactsmenu/findOne', 'verb' => 'POST'],
		['name' => 'AutoComplete#get', 'url' => 'autocomplete/get', 'verb' => 'GET'],
		['name' => 'WalledGarden#get', 'url' => '/204', 'verb' => 'GET'],
	],
	'ocs' => [
		['root' => '/cloud', 'name' => 'OCS#getCapabilities', 'url' => '/capabilities', 'verb' => 'GET'],
		['root' => '', 'name' => 'OCS#getConfig', 'url' => '/config', 'verb' => 'GET'],
		['root' => '/person', 'name' => 'OCS#personCheck', 'url' => '/check', 'verb' => 'POST'],
		['root' => '/identityproof', 'name' => 'OCS#getIdentityProof', 'url' => '/key/{cloudId}', 'verb' => 'GET'],
	],
]);

// Post installation check

/** @var $this OCP\Route\IRouter */
// Core ajax actions
// Search
$this->create('search_ajax_search', '/core/search')
	->actionInclude('core/search/ajax/search.php');
// Routing
$this->create('core_ajax_update', '/core/ajax/update.php')
	->actionInclude('core/ajax/update.php');

// File routes
$this->create('files.viewcontroller.showFile', '/f/{fileid}')->action(function($urlParams) {
	$app = new \OCA\Files\AppInfo\Application($urlParams);
	$app->dispatch('ViewController', 'index');
});

// Call routes
/**
 * @suppress PhanUndeclaredClassConstant
 * @suppress PhanUndeclaredClassMethod
 */
$this->create('spreed.pagecontroller.showCall', '/call/{token}')->action(function($urlParams) {
	if (class_exists(\OCA\Spreed\AppInfo\Application::class, false)) {
		$app = new \OCA\Spreed\AppInfo\Application($urlParams);
		$app->dispatch('PageController', 'index');
	} else {
		throw new \OC\HintException('App spreed is not enabled');
	}
});

// Sharing routes
$this->create('files_sharing.sharecontroller.showShare', '/s/{token}')->action(function($urlParams) {
	if (class_exists(\OCA\Files_Sharing\AppInfo\Application::class, false)) {
		$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
		$app->dispatch('ShareController', 'showShare');
	} else {
		throw new \OC\HintException('App file sharing is not enabled');
	}
});
$this->create('files_sharing.sharecontroller.authenticate', '/s/{token}/authenticate')->post()->action(function($urlParams) {
	if (class_exists(\OCA\Files_Sharing\AppInfo\Application::class, false)) {
		$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
		$app->dispatch('ShareController', 'authenticate');
	} else {
		throw new \OC\HintException('App file sharing is not enabled');
	}
});
$this->create('files_sharing.sharecontroller.showAuthenticate', '/s/{token}/authenticate')->get()->action(function($urlParams) {
	if (class_exists(\OCA\Files_Sharing\AppInfo\Application::class, false)) {
		$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
		$app->dispatch('ShareController', 'showAuthenticate');
	} else {
		throw new \OC\HintException('App file sharing is not enabled');
	}
});
$this->create('files_sharing.sharecontroller.downloadShare', '/s/{token}/download')->get()->action(function($urlParams) {
	if (class_exists(\OCA\Files_Sharing\AppInfo\Application::class, false)) {
		$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
		$app->dispatch('ShareController', 'downloadShare');
	} else {
		throw new \OC\HintException('App file sharing is not enabled');
	}
});
$this->create('files_sharing.publicpreview.directLink', '/s/{token}/preview')->get()->action(function($urlParams) {
	if (class_exists(\OCA\Files_Sharing\AppInfo\Application::class, false)) {
		$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
		$app->dispatch('PublicPreviewController', 'directLink');
	} else {
		throw new \OC\HintException('App file sharing is not enabled');
	}
});

// used for heartbeat
$this->create('heartbeat', '/heartbeat')->action(function(){
	// do nothing
});

<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OCP\Security\CSRF\ICsrfValidator;
use Psr\Log\LoggerInterface;

// no php execution timeout for webdav
if (!str_contains(@ini_get('disable_functions'), 'set_time_limit')) {
	@set_time_limit(0);
}
ignore_user_abort(true);

// Turn off output buffering to prevent memory problems
\OC_Util::obEnd();

$dispatcher = \OC::$server->get(\OCP\EventDispatcher\IEventDispatcher::class);

$serverFactory = new \OCA\DAV\Connector\Sabre\ServerFactory(
	\OC::$server->getConfig(),
	\OC::$server->get(LoggerInterface::class),
	\OC::$server->getDatabaseConnection(),
	\OC::$server->getUserSession(),
	\OC::$server->getMountManager(),
	\OC::$server->getTagManager(),
	\OC::$server->getRequest(),
	\OC::$server->getPreviewManager(),
	$dispatcher,
	\OC::$server->getL10N('dav')
);

// Backends
$authBackend = new \OCA\DAV\Connector\Sabre\Auth(
	\OC::$server->getSession(),
	\OC::$server->getUserSession(),
	\OC::$server->getRequest(),
	\OC::$server->getTwoFactorAuthManager(),
	\OC::$server->getBruteForceThrottler(),
	\OC::$server->get(ICsrfValidator::class),
	'principals/'
);
$authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);
$bearerAuthPlugin = new \OCA\DAV\Connector\Sabre\BearerAuth(
	\OC::$server->getUserSession(),
	\OC::$server->getSession(),
	\OC::$server->getRequest()
);
$authPlugin->addBackend($bearerAuthPlugin);

$requestUri = \OC::$server->getRequest()->getRequestUri();

$server = $serverFactory->createServer($baseuri, $requestUri, $authPlugin, function () {
	// use the view for the logged in user
	return \OC\Files\Filesystem::getView();
});

// allow setup of additional plugins
$event = new \OCP\SabrePluginEvent($server);
$dispatcher->dispatch('OCA\DAV\Connector\Sabre::addPlugin', $event);
$event = new \OCA\DAV\Events\SabrePluginAddEvent($server);
$dispatcher->dispatchTyped($event);

// And off we go!
$server->exec();

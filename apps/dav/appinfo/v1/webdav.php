<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Ko- <k.stoffelen@cs.ru.nl>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
// no php execution timeout for webdav
use OC\Authentication\TwoFactorAuth\Manager;
use OC\Files\Filesystem;
use OC\Security\Bruteforce\Throttler;
use OCA\DAV\Connector\Sabre\BearerAuth;
use OCA\DAV\Events\SabreAddPluginEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ITagManager;
use OCP\IUserSession;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\SabrePluginEvent;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Auth\Plugin as AuthPlugin;

if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
	@set_time_limit(0);
}
ignore_user_abort(true);

// Turn off output buffering to prevent memory problems
\OC_Util::obEnd();

/** @var IUserSession $userSession */
$userSession = \OC::$server->get(IUserSession::class);
/** @var IRequest $request */
$request = \OC::$server->get(IRequest::class);

$serverFactory = new OCA\DAV\Connector\Sabre\ServerFactory(
	\OC::$server->get(IConfig::class),
	\OC::$server->get(LoggerInterface::class),
	\OC::$server->get(IDBConnection::class),
	$userSession,
	\OC::$server->get(IMountManager::class),
	\OC::$server->get(ITagManager::class),
	$request,
	\OC::$server->get(IPreview::class),
	\OC::$server->get(IEventDispatcher::class),
	\OC::$server->get(IL10NFactory::class)->get('dav')
);

// Backends
$authBackend = new \OCA\DAV\Connector\Sabre\Auth(
	$userSession->getSession(),
	$userSession,
	$request,
	\OC::$server->get(Manager::class),
	\OC::$server->get(Throttler::class),
	'principals/'
);
$authPlugin = new AuthPlugin($authBackend);
$bearerAuthPlugin = new BearerAuth(
	$userSession,
	$userSession->getSession(),
	$request
);
$authPlugin->addBackend($bearerAuthPlugin);

$server = $serverFactory->createServer($baseuri, $request->getRequestUri(), $authPlugin, function () {
	// use the view for the logged in user
	return Filesystem::getView();
});

$dispatcher = \OC::$server->getEventDispatcher();
$newDispatcher = \OC::$server->get(IEventDispatcher::class);
// allow setup of additional plugins
$newDispatcher->dispatchTyped(new SabreAddPluginEvent($server));
$event = new SabrePluginEvent($server);
$dispatcher->dispatch('OCA\DAV\Connector\Sabre::addPlugin', $event);

// And off we go!
$server->start();

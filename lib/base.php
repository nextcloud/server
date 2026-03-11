<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OC\Profiler\BuiltInProfiler;
use OC\Share20\GroupDeletedListener;
use OC\Share20\Hooks;
use OC\Share20\UserDeletedListener;
use OC\Share20\UserRemovedListener;
use OC\User\DisabledUserException;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\BeforeFileSystemSetupEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Server;
use OCP\Template\ITemplateManager;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use function OCP\Log\logger;

require_once 'public/Constants.php';

/**
 * Class that is a namespace for all global OC variables
 * No, we can not put this class in its own file because it is used by
 * OC_autoload!
 */
class OC {
	/**
	 * The installation path for Nextcloud  on the server (e.g. /srv/http/nextcloud)
	 */
	public static string $SERVERROOT = '';
	/**
	 * the current request path relative to the Nextcloud root (e.g. files/index.php)
	 */
	private static string $SUBURI = '';
	/**
	 * the Nextcloud root path for http requests (e.g. /nextcloud)
	 */
	public static string $WEBROOT = '';
	/**
	 * The installation path array of the apps folder on the server (e.g. /srv/http/nextcloud) 'path' and
	 * web path in 'url'
	 */
	public static array $APPSROOTS = [];

	public static string $configDir;

	/**
	 * requested app
	 */
	public static string $REQUESTEDAPP = '';

	/**
	 * check if Nextcloud runs in cli mode
	 */
	public static bool $CLI = false;

	public static \Composer\Autoload\ClassLoader $composerAutoloader;

	public static \OC\Server $server;
}

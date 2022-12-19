<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\Files_Sharing\AppInfo;

use OC\Group\DisplayNameCache as GroupDisplayNameCache;
use OC\Share\Share;
use OC\User\DisplayNameCache;
use OCA\Files_Sharing\Capabilities;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCA\Files_Sharing\External\Manager;
use OCA\Files_Sharing\External\MountProvider as ExternalMountProvider;
use OCA\Files_Sharing\Helper;
use OCA\Files_Sharing\Listener\LegacyBeforeTemplateRenderedListener;
use OCA\Files_Sharing\Listener\LoadAdditionalListener;
use OCA\Files_Sharing\Listener\LoadSidebarListener;
use OCA\Files_Sharing\Listener\ShareInteractionListener;
use OCA\Files_Sharing\Listener\UserAddedToGroupListener;
use OCA\Files_Sharing\Listener\UserShareAcceptanceListener;
use OCA\Files_Sharing\Middleware\OCSShareAPIMiddleware;
use OCA\Files_Sharing\Middleware\ShareInfoMiddleware;
use OCA\Files_Sharing\Middleware\SharingCheckMiddleware;
use OCA\Files_Sharing\MountProvider;
use OCA\Files_Sharing\Notification\Listener;
use OCA\Files_Sharing\Notification\Notifier;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files\Event\LoadSidebar;
use OCP\Files\Event\BeforeDirectGetEvent;
use OCA\Files_Sharing\ShareBackend\File;
use OCA\Files_Sharing\ShareBackend\Folder;
use OCA\Files_Sharing\ViewOnly;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent as ResourcesLoadAdditionalScriptsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\GenericEvent;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Events\BeforeDirectFileDownloadEvent;
use OCP\Files\Events\BeforeZipCreatedEvent;
use OCP\Files\IRootFolder;
use OCP\Group\Events\GroupChangedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IManager;
use OCP\User\Events\UserChangedEvent;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent as OldGenericEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'files_sharing';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerService(ExternalMountProvider::class, function (ContainerInterface $c) {
			return new ExternalMountProvider(
				$c->get(IDBConnection::class),
				function () use ($c) {
					return $c->get(Manager::class);
				},
				$c->get(ICloudIdManager::class)
			);
		});

		/**
		 * Middleware
		 */
		$context->registerMiddleWare(SharingCheckMiddleware::class);
		$context->registerMiddleWare(OCSShareAPIMiddleware::class);
		$context->registerMiddleWare(ShareInfoMiddleware::class);

		$context->registerCapability(Capabilities::class);

		$context->registerNotifierService(Notifier::class);
		$context->registerEventListener(UserChangedEvent::class, DisplayNameCache::class);
		$context->registerEventListener(GroupChangedEvent::class, GroupDisplayNameCache::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn([$this, 'registerMountProviders']);
		$context->injectFn([$this, 'registerEventsScripts']);
		$context->injectFn([$this, 'registerDownloadEvents']);
		$context->injectFn([$this, 'setupSharingMenus']);

		Helper::registerHooks();

		Share::registerBackend('file', File::class);
		Share::registerBackend('folder', Folder::class, 'file');

		/**
		 * Always add main sharing script
		 */
		Util::addScript(self::APP_ID, 'main');
	}


	public function registerMountProviders(IMountProviderCollection $mountProviderCollection, MountProvider $mountProvider, ExternalMountProvider $externalMountProvider): void {
		$mountProviderCollection->registerProvider($mountProvider);
		$mountProviderCollection->registerProvider($externalMountProvider);
	}

	public function registerEventsScripts(IEventDispatcher $dispatcher, EventDispatcherInterface $oldDispatcher): void {
		// sidebar and files scripts
		$dispatcher->addServiceListener(LoadAdditionalScriptsEvent::class, LoadAdditionalListener::class);
		$dispatcher->addServiceListener(BeforeTemplateRenderedEvent::class, LegacyBeforeTemplateRenderedListener::class);
		$dispatcher->addServiceListener(LoadSidebar::class, LoadSidebarListener::class);
		$dispatcher->addServiceListener(ShareCreatedEvent::class, ShareInteractionListener::class);
		$dispatcher->addServiceListener(ShareCreatedEvent::class, UserShareAcceptanceListener::class);
		$dispatcher->addServiceListener(UserAddedEvent::class, UserAddedToGroupListener::class);
		$dispatcher->addListener(ResourcesLoadAdditionalScriptsEvent::class, function () {
			\OCP\Util::addScript('files_sharing', 'collaboration');
		});

		// notifications api to accept incoming user shares
		$oldDispatcher->addListener('OCP\Share::postShare', function (OldGenericEvent $event) {
			/** @var Listener $listener */
			$listener = $this->getContainer()->query(Listener::class);
			$listener->shareNotification($event);
		});
		$oldDispatcher->addListener(IGroup::class . '::postAddUser', function (OldGenericEvent $event) {
			/** @var Listener $listener */
			$listener = $this->getContainer()->query(Listener::class);
			$listener->userAddedToGroup($event);
		});
	}

	public function registerDownloadEvents(
		IEventDispatcher $dispatcher,
		IUserSession $userSession,
		IRootFolder $rootFolder
	): void {

		$dispatcher->addListener(
			BeforeDirectFileDownloadEvent::class,
			function (BeforeDirectFileDownloadEvent $event) use ($userSession, $rootFolder): void {
				$pathsToCheck = [$event->getPath()];
				// Check only for user/group shares. Don't restrict e.g. share links
				$user = $userSession->getUser();
				if ($user) {
					$viewOnlyHandler = new ViewOnly(
						$rootFolder->getUserFolder($user->getUID())
					);
					if (!$viewOnlyHandler->check($pathsToCheck)) {
						$event->setSuccessful(false);
						$event->setErrorMessage('Access to this resource or one of its sub-items has been denied.');
					}
				}
			}
		);

		$dispatcher->addListener(
			BeforeZipCreatedEvent::class,
			function (BeforeZipCreatedEvent $event) use ($userSession, $rootFolder): void {
				$dir = $event->getDirectory();
				$files = $event->getFiles();

				$pathsToCheck = [];
				foreach ($files as $file) {
					$pathsToCheck[] = $dir . '/' . $file;
				}

				// Check only for user/group shares. Don't restrict e.g. share links
				$user = $userSession->getUser();
				if ($user) {
					$viewOnlyHandler = new ViewOnly(
						$rootFolder->getUserFolder($user->getUID())
					);
					if (!$viewOnlyHandler->check($pathsToCheck)) {
						$event->setErrorMessage('Access to this resource or one of its sub-items has been denied.');
						$event->setSuccessful(false);
					} else {
						$event->setSuccessful(true);
					}
				} else {
					$event->setSuccessful(true);
				}
			}
		);
	}

	public function setupSharingMenus(IManager $shareManager, IFactory $l10nFactory, IUserSession $userSession): void {
		if (!$shareManager->shareApiEnabled() || !class_exists('\OCA\Files\App')) {
			return;
		}

		$navigationManager = \OCA\Files\App::getNavigationManager();
		// show_Quick_Access stored as string
		$navigationManager->add(function () use ($shareManager, $l10nFactory, $userSession) {
			$l = $l10nFactory->get('files_sharing');
			$user = $userSession->getUser();
			$userId = $user ? $user->getUID() : null;

			$sharingSublistArray = [];

			if ($shareManager->sharingDisabledForUser($userId) === false) {
				$sharingSublistArray[] = [
					'id' => 'sharingout',
					'appname' => 'files_sharing',
					'script' => 'list.php',
					'order' => 16,
					'name' => $l->t('Shared with others'),
				];
			}

			$sharingSublistArray[] = [
				'id' => 'sharingin',
				'appname' => 'files_sharing',
				'script' => 'list.php',
				'order' => 15,
				'name' => $l->t('Shared with you'),
			];

			if ($shareManager->sharingDisabledForUser($userId) === false) {
				// Check if sharing by link is enabled
				if ($shareManager->shareApiAllowLinks()) {
					$sharingSublistArray[] = [
						'id' => 'sharinglinks',
						'appname' => 'files_sharing',
						'script' => 'list.php',
						'order' => 17,
						'name' => $l->t('Shared by link'),
					];
				}
			}

			$sharingSublistArray[] = [
				'id' => 'deletedshares',
				'appname' => 'files_sharing',
				'script' => 'list.php',
				'order' => 19,
				'name' => $l->t('Deleted shares'),
			];

			$sharingSublistArray[] = [
				'id' => 'pendingshares',
				'appname' => 'files_sharing',
				'script' => 'list.php',
				'order' => 19,
				'name' => $l->t('Pending shares'),
			];

			return [
				'id' => 'shareoverview',
				'appname' => 'files_sharing',
				'script' => 'list.php',
				'order' => 18,
				'name' => $l->t('Shares'),
				'classes' => 'collapsible',
				'sublist' => $sharingSublistArray,
				'expandedState' => 'show_sharing_menu'
			];
		});
	}
}

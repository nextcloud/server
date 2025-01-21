<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\AppInfo;

use OCA\DAV\Connector\Sabre\Principal;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCA\Files_Trashbin\Capabilities;
use OCA\Files_Trashbin\Events\BeforeNodeRestoredEvent;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Listener\EventListener;
use OCA\Files_Trashbin\Listeners\BeforeTemplateRendered;
use OCA\Files_Trashbin\Listeners\LoadAdditionalScripts;
use OCA\Files_Trashbin\Listeners\SyncLivePhotosListener;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCA\Files_Trashbin\Trash\TrashManager;
use OCA\Files_Trashbin\Trashbin;
use OCA\Files_Trashbin\UserMigration\TrashbinMigrator;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Events\BeforeFileSystemSetupEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\User\Events\BeforeUserDeletedEvent;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'files_trashbin';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);

		$context->registerServiceAlias('Expiration', Expiration::class);
		$context->registerServiceAlias(ITrashManager::class, TrashManager::class);
		/** Register $principalBackend for the DAV collection */
		$context->registerServiceAlias('principalBackend', Principal::class);

		$context->registerUserMigrator(TrashbinMigrator::class);

		$context->registerEventListener(
			LoadAdditionalScriptsEvent::class,
			LoadAdditionalScripts::class
		);

		$context->registerEventListener(
			BeforeTemplateRenderedEvent::class,
			BeforeTemplateRendered::class
		);

		$context->registerEventListener(BeforeNodeRestoredEvent::class, SyncLivePhotosListener::class);

		$context->registerEventListener(NodeWrittenEvent::class, EventListener::class);
		$context->registerEventListener(BeforeUserDeletedEvent::class, EventListener::class);
		$context->registerEventListener(BeforeFileSystemSetupEvent::class, EventListener::class);

		// pre and post-rename, disable trash logic for the copy+unlink case
		$context->registerEventListener(BeforeNodeDeletedEvent::class, Trashbin::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn([$this, 'registerTrashBackends']);
	}

	public function registerTrashBackends(ContainerInterface $serverContainer, LoggerInterface $logger, IAppManager $appManager, ITrashManager $trashManager): void {
		foreach ($appManager->getInstalledApps() as $app) {
			$appInfo = $appManager->getAppInfo($app);
			if (isset($appInfo['trash'])) {
				$backends = $appInfo['trash'];
				foreach ($backends as $backend) {
					$class = $backend['@value'];
					$for = $backend['@attributes']['for'];

					try {
						$backendObject = $serverContainer->get($class);
						$trashManager->registerBackend($for, $backendObject);
					} catch (\Exception $e) {
						$logger->error($e->getMessage(), ['exception' => $e]);
					}
				}
			}
		}
	}
}

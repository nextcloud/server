<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Bootstrap;

use OC\Support\CrashReport\Registry;
use OC_App;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\QueryException;
use OCP\Dashboard\IManager;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IServerContainer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use function class_exists;
use function class_implements;
use function in_array;

class Coordinator {
	/** @var RegistrationContext|null */
	private $registrationContext;

	/** @var array<string,true> */
	private array $bootedApps = [];

	public function __construct(
		private IServerContainer $serverContainer,
		private Registry $registry,
		private IManager $dashboardManager,
		private IEventDispatcher $eventDispatcher,
		private IEventLogger $eventLogger,
		private IAppManager $appManager,
		private LoggerInterface $logger,
	) {
	}

	public function runInitialRegistration(): void {
		$this->registerApps(OC_App::getEnabledApps());
	}

	public function runLazyRegistration(string $appId): void {
		$this->registerApps([$appId]);
	}

	/**
	 * @param string[] $appIds
	 */
	private function registerApps(array $appIds): void {
		$this->eventLogger->start('bootstrap:register_apps', '');
		if ($this->registrationContext === null) {
			$this->registrationContext = new RegistrationContext($this->logger);
		}
		$apps = [];
		foreach ($appIds as $appId) {
			$this->eventLogger->start("bootstrap:register_app:$appId", "Register $appId");
			$this->eventLogger->start("bootstrap:register_app:$appId:autoloader", "Setup autoloader for $appId");
			/*
			 * First, we have to enable the app's autoloader
			 */
			try {
				$path = $this->appManager->getAppPath($appId);
				OC_App::registerAutoloading($appId, $path);
			} catch (AppPathNotFoundException) {
				// Ignore
				continue;
			}
			$this->eventLogger->end("bootstrap:register_app:$appId:autoloader");

			/*
			 * Next we check if there is an application class, and it implements
			 * the \OCP\AppFramework\Bootstrap\IBootstrap interface
			 */
			if ($appId === 'core') {
				$appNameSpace = 'OC\\Core';
			} else {
				$appNameSpace = App::buildAppNamespace($appId);
			}
			$applicationClassName = $appNameSpace . '\\AppInfo\\Application';

			try {
				if (class_exists($applicationClassName) && is_a($applicationClassName, IBootstrap::class, true)) {
					$this->eventLogger->start("bootstrap:register_app:$appId:application", "Load `Application` instance for $appId");
					try {
						/** @var IBootstrap&App $application */
						$application = $this->serverContainer->query($applicationClassName);
						$apps[$appId] = $application;
					} catch (ContainerExceptionInterface $e) {
						// Weird, but ok
						$this->eventLogger->end("bootstrap:register_app:$appId");
						continue;
					}
					$this->eventLogger->end("bootstrap:register_app:$appId:application");

					$this->eventLogger->start("bootstrap:register_app:$appId:register", "`Application::register` for $appId");
					$application->register($this->registrationContext->for($appId));
					$this->eventLogger->end("bootstrap:register_app:$appId:register");
				}
			} catch (Throwable $e) {
				$this->logger->emergency('Error during app service registration: ' . $e->getMessage(), [
					'exception' => $e,
					'app' => $appId,
				]);
				$this->eventLogger->end("bootstrap:register_app:$appId");
				continue;
			}
			$this->eventLogger->end("bootstrap:register_app:$appId");
		}

		$this->eventLogger->start('bootstrap:register_apps:apply', 'Apply all the registered service by apps');
		/**
		 * Now that all register methods have been called, we can delegate the registrations
		 * to the actual services
		 */
		$this->registrationContext->delegateCapabilityRegistrations($apps);
		$this->registrationContext->delegateCrashReporterRegistrations($apps, $this->registry);
		$this->registrationContext->delegateDashboardPanelRegistrations($this->dashboardManager);
		$this->registrationContext->delegateEventListenerRegistrations($this->eventDispatcher);
		$this->registrationContext->delegateContainerRegistrations($apps);
		$this->eventLogger->end('bootstrap:register_apps:apply');
		$this->eventLogger->end('bootstrap:register_apps');
	}

	public function getRegistrationContext(): ?RegistrationContext {
		return $this->registrationContext;
	}

	public function bootApp(string $appId): void {
		if (isset($this->bootedApps[$appId])) {
			return;
		}
		$this->bootedApps[$appId] = true;

		$appNameSpace = App::buildAppNamespace($appId);
		$applicationClassName = $appNameSpace . '\\AppInfo\\Application';
		if (!class_exists($applicationClassName)) {
			// Nothing to boot
			return;
		}

		/*
		 * Now it is time to fetch an instance of the App class. For classes
		 * that implement \OCP\AppFramework\Bootstrap\IBootstrap this means
		 * the instance was already created for register, but any other
		 * (legacy) code will now do their magic via the constructor.
		 */
		$this->eventLogger->start('bootstrap:boot_app:' . $appId, "Call `Application::boot` for $appId");
		try {
			/** @var App $application */
			$application = $this->serverContainer->query($applicationClassName);
			if ($application instanceof IBootstrap) {
				/** @var BootContext $context */
				$context = new BootContext($application->getContainer());
				$application->boot($context);
			}
		} catch (QueryException $e) {
			$this->logger->error("Could not boot $appId: " . $e->getMessage(), [
				'exception' => $e,
			]);
		} catch (Throwable $e) {
			$this->logger->emergency("Could not boot $appId: " . $e->getMessage(), [
				'exception' => $e,
			]);
		}
		$this->eventLogger->end('bootstrap:boot_app:' . $appId);
	}

	public function isBootable(string $appId) {
		$appNameSpace = App::buildAppNamespace($appId);
		$applicationClassName = $appNameSpace . '\\AppInfo\\Application';
		return class_exists($applicationClassName) &&
			in_array(IBootstrap::class, class_implements($applicationClassName), true);
	}
}

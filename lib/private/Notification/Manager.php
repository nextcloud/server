<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Notification;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUserManager;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\IApp;
use OCP\Notification\IDeferrableApp;
use OCP\Notification\IDismissableNotifier;
use OCP\Notification\IManager;
use OCP\Notification\IncompleteNotificationException;
use OCP\Notification\IncompleteParsedNotificationException;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;
use OCP\RichObjectStrings\IValidator;
use OCP\Support\Subscription\IRegistry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;

class Manager implements IManager {
	/** @var ICache */
	protected ICache $cache;

	/** @var IApp[] */
	protected array $apps;
	/** @var string[] */
	protected array $appClasses;

	/** @var INotifier[] */
	protected array $notifiers;
	/** @var string[] */
	protected array $notifierClasses;

	/** @var bool */
	protected bool $preparingPushNotification;
	/** @var bool */
	protected bool $deferPushing;
	/** @var bool */
	private bool $parsedRegistrationContext;

	public function __construct(
		protected IValidator $validator,
		private IUserManager $userManager,
		ICacheFactory $cacheFactory,
		protected IRegistry $subscription,
		protected LoggerInterface $logger,
		private Coordinator $coordinator,
	) {
		$this->cache = $cacheFactory->createDistributed('notifications');

		$this->apps = [];
		$this->notifiers = [];
		$this->appClasses = [];
		$this->notifierClasses = [];
		$this->preparingPushNotification = false;
		$this->deferPushing = false;
		$this->parsedRegistrationContext = false;
	}
	public function registerApp(string $appClass): void {
		$this->appClasses[] = $appClass;
	}

	public function registerNotifier(\Closure $service, \Closure $info): void {
		$infoData = $info();
		$exception = new \InvalidArgumentException(
			'Notifier ' . $infoData['name'] . ' (id: ' . $infoData['id'] . ') is not considered because it is using the old way to register.'
		);
		$this->logger->error($exception->getMessage(), ['exception' => $exception]);
	}

	public function registerNotifierService(string $notifierService): void {
		$this->notifierClasses[] = $notifierService;
	}

	/**
	 * @return IApp[]
	 */
	protected function getApps(): array {
		if (empty($this->appClasses)) {
			return $this->apps;
		}

		foreach ($this->appClasses as $appClass) {
			try {
				$app = \OC::$server->get($appClass);
			} catch (ContainerExceptionInterface $e) {
				$this->logger->error('Failed to load notification app class: ' . $appClass, [
					'exception' => $e,
					'app' => 'notifications',
				]);
				continue;
			}

			if (!($app instanceof IApp)) {
				$this->logger->error('Notification app class ' . $appClass . ' is not implementing ' . IApp::class, [
					'app' => 'notifications',
				]);
				continue;
			}

			$this->apps[] = $app;
		}

		$this->appClasses = [];

		return $this->apps;
	}

	public function getNotifiers(): array {
		if (!$this->parsedRegistrationContext) {
			$notifierServices = $this->coordinator->getRegistrationContext()->getNotifierServices();
			foreach ($notifierServices as $notifierService) {
				try {
					$notifier = \OC::$server->get($notifierService->getService());
				} catch (ContainerExceptionInterface $e) {
					$this->logger->error('Failed to load notification notifier class: ' . $notifierService->getService(), [
						'exception' => $e,
						'app' => 'notifications',
					]);
					continue;
				}

				if (!($notifier instanceof INotifier)) {
					$this->logger->error('Notification notifier class ' . $notifierService->getService() . ' is not implementing ' . INotifier::class, [
						'app' => 'notifications',
					]);
					continue;
				}

				$this->notifiers[] = $notifier;
			}

			$this->parsedRegistrationContext = true;
		}

		if (empty($this->notifierClasses)) {
			return $this->notifiers;
		}

		foreach ($this->notifierClasses as $notifierClass) {
			try {
				$notifier = \OC::$server->get($notifierClass);
			} catch (ContainerExceptionInterface $e) {
				$this->logger->error('Failed to load notification notifier class: ' . $notifierClass, [
					'exception' => $e,
					'app' => 'notifications',
				]);
				continue;
			}

			if (!($notifier instanceof INotifier)) {
				$this->logger->error('Notification notifier class ' . $notifierClass . ' is not implementing ' . INotifier::class, [
					'app' => 'notifications',
				]);
				continue;
			}

			$this->notifiers[] = $notifier;
		}

		$this->notifierClasses = [];

		return $this->notifiers;
	}

	public function createNotification(): INotification {
		return new Notification($this->validator);
	}

	public function hasNotifiers(): bool {
		return !empty($this->notifiers) || !empty($this->notifierClasses);
	}

	public function setPreparingPushNotification(bool $preparingPushNotification): void {
		$this->preparingPushNotification = $preparingPushNotification;
	}

	public function isPreparingPushNotification(): bool {
		return $this->preparingPushNotification;
	}

	public function defer(): bool {
		$alreadyDeferring = $this->deferPushing;
		$this->deferPushing = true;

		$apps = $this->getApps();

		foreach ($apps as $app) {
			if ($app instanceof IDeferrableApp) {
				$app->defer();
			}
		}

		return !$alreadyDeferring;
	}

	public function flush(): void {
		$apps = $this->getApps();

		foreach ($apps as $app) {
			if (!$app instanceof IDeferrableApp) {
				continue;
			}

			try {
				$app->flush();
			} catch (\InvalidArgumentException $e) {
			}
		}

		$this->deferPushing = false;
	}

	public function isFairUseOfFreePushService(): bool {
		$pushAllowed = $this->cache->get('push_fair_use');
		if ($pushAllowed === null) {
			/**
			 * We want to keep offering our push notification service for free, but large
			 * users overload our infrastructure. For this reason we have to rate-limit the
			 * use of push notifications. If you need this feature, consider using Nextcloud Enterprise.
			 */
			$isFairUse = $this->subscription->delegateHasValidSubscription() || $this->userManager->countSeenUsers() < 1000;
			$pushAllowed = $isFairUse ? 'yes' : 'no';
			$this->cache->set('push_fair_use', $pushAllowed, 3600);
		}
		return $pushAllowed === 'yes';
	}

	public function notify(INotification $notification): void {
		if (!$notification->isValid()) {
			throw new IncompleteNotificationException('The given notification is invalid');
		}

		$apps = $this->getApps();

		foreach ($apps as $app) {
			try {
				$app->notify($notification);
			} catch (IncompleteNotificationException) {
			} catch (\InvalidArgumentException $e) {
				// todo 33.0.0 Log as warning
				// todo 39.0.0 Log as error
				$this->logger->debug(get_class($app) . '::notify() threw \InvalidArgumentException which is deprecated. Throw \OCP\Notification\IncompleteNotificationException when the notification is incomplete for your app and otherwise handle all \InvalidArgumentException yourself.');
			}
		}
	}

	public function getID(): string {
		return 'core';
	}

	public function getName(): string {
		return 'core';
	}

	public function prepare(INotification $notification, string $languageCode): INotification {
		$notifiers = $this->getNotifiers();

		foreach ($notifiers as $notifier) {
			try {
				$notification = $notifier->prepare($notification, $languageCode);
			} catch (AlreadyProcessedException $e) {
				$this->markProcessed($notification);
				throw $e;
			} catch (UnknownNotificationException) {
				continue;
			} catch (\InvalidArgumentException $e) {
				// todo 33.0.0 Log as warning
				// todo 39.0.0 Log as error
				$this->logger->debug(get_class($notifier) . '::prepare() threw \InvalidArgumentException which is deprecated. Throw \OCP\Notification\UnknownNotificationException when the notification is not known to your notifier and otherwise handle all \InvalidArgumentException yourself.');
				continue;
			}

			if (!$notification->isValidParsed()) {
				$this->logger->info('Notification was claimed to be parsed, but was not fully parsed by ' . get_class($notifier) . ' [app: ' . $notification->getApp() . ', subject: ' . $notification->getSubject() . ']');
				throw new IncompleteParsedNotificationException();
			}
		}

		if (!$notification->isValidParsed()) {
			$this->logger->info('Notification was not parsed by any notifier [app: ' . $notification->getApp() . ', subject: ' . $notification->getSubject() . ']');
			throw new IncompleteParsedNotificationException();
		}

		$link = $notification->getLink();
		if ($link !== '' && !str_starts_with($link, 'http://') && !str_starts_with($link, 'https://')) {
			$this->logger->warning('Link of notification is not an absolute URL and does not work in mobile and desktop clients [app: ' . $notification->getApp() . ', subject: ' . $notification->getSubject() . ']');
		}

		$icon = $notification->getIcon();
		if ($icon !== '' && !str_starts_with($icon, 'http://') && !str_starts_with($icon, 'https://')) {
			$this->logger->warning('Icon of notification is not an absolute URL and does not work in mobile and desktop clients [app: ' . $notification->getApp() . ', subject: ' . $notification->getSubject() . ']');
		}

		foreach ($notification->getParsedActions() as $action) {
			$link = $action->getLink();
			if ($link !== '' && !str_starts_with($link, 'http://') && !str_starts_with($link, 'https://')) {
				$this->logger->warning('Link of action is not an absolute URL and does not work in mobile and desktop clients [app: ' . $notification->getApp() . ', subject: ' . $notification->getSubject() . ']');
			}
		}

		return $notification;
	}

	public function markProcessed(INotification $notification): void {
		$apps = $this->getApps();

		foreach ($apps as $app) {
			$app->markProcessed($notification);
		}
	}

	public function getCount(INotification $notification): int {
		$apps = $this->getApps();

		$count = 0;
		foreach ($apps as $app) {
			$count += $app->getCount($notification);
		}

		return $count;
	}

	public function dismissNotification(INotification $notification): void {
		$notifiers = $this->getNotifiers();

		foreach ($notifiers as $notifier) {
			if ($notifier instanceof IDismissableNotifier) {
				try {
					$notifier->dismissNotification($notification);
				} catch (UnknownNotificationException) {
					continue;
				} catch (\InvalidArgumentException $e) {
					// todo 33.0.0 Log as warning
					// todo 39.0.0 Log as error
					$this->logger->debug(get_class($notifier) . '::dismissNotification() threw \InvalidArgumentException which is deprecated. Throw \OCP\Notification\UnknownNotificationException when the notification is not known to your notifier and otherwise handle all \InvalidArgumentException yourself.');
					continue;
				}
			}
		}
	}
}

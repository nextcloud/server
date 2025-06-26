<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Support\Subscription;

use OCP\AppFramework\QueryException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IServerContainer;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Support\Subscription\Exception\AlreadyRegisteredException;
use OCP\Support\Subscription\IRegistry;
use OCP\Support\Subscription\ISubscription;
use OCP\Support\Subscription\ISupportedApps;
use Psr\Log\LoggerInterface;

class Registry implements IRegistry {
	/** @var ISubscription */
	private $subscription = null;

	/** @var string */
	private $subscriptionService = null;

	/** @var IConfig */
	private $config;

	/** @var IServerContainer */
	private $container;
	/** @var IUserManager */
	private $userManager;
	/** @var IGroupManager */
	private $groupManager;
	/** @var LoggerInterface */
	private $logger;

	public function __construct(IConfig $config,
		IServerContainer $container,
		IUserManager $userManager,
		IGroupManager $groupManager,
		LoggerInterface $logger) {
		$this->config = $config;
		$this->container = $container;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
	}

	private function getSubscription(): ?ISubscription {
		if ($this->subscription === null && $this->subscriptionService !== null) {
			try {
				$this->subscription = $this->container->query($this->subscriptionService);
			} catch (QueryException $e) {
				// Ignore this
			}
		}

		return $this->subscription;
	}

	/**
	 * Register a subscription instance. In case it is called multiple times the
	 * first one is used.
	 *
	 * @param ISubscription $subscription
	 * @throws AlreadyRegisteredException
	 *
	 * @since 17.0.0
	 */
	public function register(ISubscription $subscription): void {
		if ($this->subscription !== null || $this->subscriptionService !== null) {
			throw new AlreadyRegisteredException();
		}
		$this->subscription = $subscription;
	}

	public function registerService(string $subscriptionService): void {
		if ($this->subscription !== null || $this->subscriptionService !== null) {
			throw new AlreadyRegisteredException();
		}

		$this->subscriptionService = $subscriptionService;
	}


	/**
	 * Fetches the list of app IDs that are supported by the subscription
	 *
	 * @since 17.0.0
	 */
	public function delegateGetSupportedApps(): array {
		if ($this->getSubscription() instanceof ISupportedApps) {
			return $this->getSubscription()->getSupportedApps();
		}
		return [];
	}

	/**
	 * Indicates if a valid subscription is available
	 *
	 * @since 17.0.0
	 */
	public function delegateHasValidSubscription(): bool {
		// Allow overwriting this manually for environments where the subscription information cannot be fetched
		if ($this->config->getSystemValueBool('has_valid_subscription')) {
			return true;
		}

		if ($this->getSubscription() instanceof ISubscription) {
			return $this->getSubscription()->hasValidSubscription();
		}
		return false;
	}

	/**
	 * Indicates if the subscription has extended support
	 *
	 * @since 17.0.0
	 */
	public function delegateHasExtendedSupport(): bool {
		if ($this->getSubscription() instanceof ISubscription) {
			return $this->getSubscription()->hasExtendedSupport();
		}
		return false;
	}


	/**
	 * Indicates if a hard user limit is reached and no new users should be created
	 *
	 * @param IManager|null $notificationManager
	 * @since 21.0.0
	 */
	public function delegateIsHardUserLimitReached(?IManager $notificationManager = null): bool {
		$subscription = $this->getSubscription();
		if ($subscription instanceof ISubscription
			&& $subscription->hasValidSubscription()) {
			$userLimitReached = $subscription->isHardUserLimitReached();
			if ($userLimitReached && $notificationManager instanceof IManager) {
				$this->notifyAboutReachedUserLimit($notificationManager);
			}
			return $userLimitReached;
		}

		$isOneClickInstance = $this->config->getSystemValueBool('one-click-instance', false);

		if (!$isOneClickInstance) {
			return false;
		}

		$userCount = $this->getUserCount();
		$hardUserLimit = $this->config->getSystemValueInt('one-click-instance.user-limit', 50);

		$userLimitReached = $userCount >= $hardUserLimit;
		if ($userLimitReached && $notificationManager instanceof IManager) {
			$this->notifyAboutReachedUserLimit($notificationManager);
		}
		return $userLimitReached;
	}

	private function getUserCount(): int {
		/* We cannot limit because we substract disabled users afterward. But we limit to mapped users so should be not too expensive. */
		$userCount = (int)$this->userManager->countUsersTotal(0, true);

		$disabledUsers = $this->config->getUsersForUserValue('core', 'enabled', 'false');
		$disabledUsersCount = count($disabledUsers);
		$userCount = $userCount - $disabledUsersCount;

		if ($userCount < 0) {
			$userCount = 0;

			// this should never happen
			$this->logger->warning("Total user count was negative (users: $userCount, disabled: $disabledUsersCount)", ['app' => 'lib']);
		}

		return $userCount;
	}

	private function notifyAboutReachedUserLimit(IManager $notificationManager): void {
		$admins = $this->groupManager->get('admin')->getUsers();

		$notification = $notificationManager->createNotification();
		$notification->setApp('core')
			->setObject('user_limit_reached', '1')
			->setSubject('user_limit_reached');

		if ($notificationManager->getCount($notification) > 0
			&& !$this->reIssue()
		) {
			return;
		}

		$notificationManager->markProcessed($notification);
		$notification->setDateTime(new \DateTime());

		foreach ($admins as $admin) {
			$notification->setUser($admin->getUID());
			$notificationManager->notify($notification);
		}

		$this->logger->warning('The account limit was reached and the new account was not created', ['app' => 'lib']);
	}

	protected function reIssue(): bool {
		$lastNotification = (int)$this->config->getAppValue('lib', 'last_subscription_reminder', '0');

		if ((time() - $lastNotification) >= 86400) {
			$this->config->setAppValue('lib', 'last_subscription_reminder', (string)time());
			return true;
		}
		return false;
	}
}

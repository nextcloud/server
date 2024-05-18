<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCP\Notification;

/**
 * Interface IManager
 *
 * @since 9.0.0
 */
interface IManager extends IApp, INotifier {
	/**
	 * @param string $appClass The service must implement IApp, otherwise a
	 *                          \InvalidArgumentException is thrown later
	 * @since 17.0.0
	 */
	public function registerApp(string $appClass): void;

	/**
	 * @param \Closure $service The service must implement INotifier, otherwise a
	 *                          \InvalidArgumentException is thrown later
	 * @param \Closure $info    An array with the keys 'id' and 'name' containing
	 *                          the app id and the app name
	 * @deprecated 17.0.0 use registerNotifierService instead.
	 * @since 8.2.0 - Parameter $info was added in 9.0.0
	 */
	public function registerNotifier(\Closure $service, \Closure $info);

	/**
	 * @param string $notifierService The service must implement INotifier, otherwise a
	 *                          \InvalidArgumentException is thrown later
	 * @since 17.0.0
	 * @deprecated 22.0.0 use the IBootStrap registration context
	 */
	public function registerNotifierService(string $notifierService): void;

	/**
	 * @return INotifier[]
	 * @since 9.0.0
	 */
	public function getNotifiers(): array;

	/**
	 * @return INotification
	 * @since 9.0.0
	 */
	public function createNotification(): INotification;

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function hasNotifiers(): bool;

	/**
	 * @param bool $preparingPushNotification
	 * @since 14.0.0
	 */
	public function setPreparingPushNotification(bool $preparingPushNotification): void;

	/**
	 * @return bool
	 * @since 14.0.0
	 */
	public function isPreparingPushNotification(): bool;

	/**
	 * @since 18.0.0
	 */
	public function dismissNotification(INotification $notification): void;

	/**
	 * Start deferring notifications until `flush()` is called
	 *
	 * The calling app should only "flush" when it got returned true on the defer call,
	 * otherwise another app is deferring the sending already.
	 * @return bool
	 * @since 20.0.0
	 */
	public function defer(): bool;

	/**
	 * Send all deferred notifications that have been stored since `defer()` was called
	 *
	 * @since 20.0.0
	 */
	public function flush(): void;

	/**
	 * Whether the server can use the hosted push notification service
	 *
	 * We want to keep offering our push notification service for free, but large
	 * users overload our infrastructure. For this reason we have to rate-limit the
	 * use of push notifications. If you need this feature, consider using Nextcloud Enterprise.
	 *
	 * @since 23.0.0
	 */
	public function isFairUseOfFreePushService(): bool;
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Activity;

use OCP\Activity\Exceptions\FilterNotFoundException;
use OCP\Activity\Exceptions\IncompleteActivityException;
use OCP\Activity\Exceptions\SettingNotFoundException;

/**
 * Interface IManager
 *
 * @since 6.0.0
 */
interface IManager {
	/**
	 * Generates a new IEvent object
	 *
	 * Make sure to call at least the following methods before sending it to the
	 * app with via the publish() method:
	 *  - setApp()
	 *  - setType()
	 *  - setAffectedUser()
	 *  - setSubject()
	 *  - setObject()
	 *
	 * @return IEvent
	 * @since 8.2.0
	 */
	public function generateEvent(): IEvent;

	/**
	 * Publish an event to the activity consumers
	 *
	 * Make sure to call at least the following methods before sending an Event:
	 *  - setApp()
	 *  - setType()
	 *  - setAffectedUser()
	 *  - setSubject()
	 *  - setObject()
	 *
	 * @param IEvent $event
	 * @throws IncompleteActivityException if required values have not been set
	 * @since 8.2.0
	 * @since 30.0.0 throws {@see IncompleteActivityException} instead of \BadMethodCallException
	 */
	public function publish(IEvent $event): void;

	/**
	 * Bulk publish an event for multiple users
	 * taking into account the app specific activity settings
	 *
	 * Make sure to call at least the following methods before sending an Event:
	 *  - setApp()
	 *  - setType()
	 *
	 * @param IEvent $event
	 * @throws IncompleteActivityException if required values have not been set
	 * @since 32.0.0
	 */
	public function bulkPublish(IEvent $event, array $affectedUserIds, ISetting $setting): void;

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * activity consumers are actually requested
	 *
	 * $callable has to return an instance of \OCP\Activity\IConsumer
	 *
	 * @param \Closure $callable
	 * @since 6.0.0
	 */
	public function registerConsumer(\Closure $callable): void;

	/**
	 * @param string $filter Class must implement OCA\Activity\IFilter
	 * @since 11.0.0
	 */
	public function registerFilter(string $filter): void;

	/**
	 * @return IFilter[]
	 * @since 11.0.0
	 */
	public function getFilters(): array;

	/**
	 * @param string $id
	 * @return IFilter
	 * @throws FilterNotFoundException when the filter was not found
	 * @since 11.0.0
	 * @since 30.0.0 throws {@see FilterNotFoundException} instead of \InvalidArgumentException
	 */
	public function getFilterById(string $id): IFilter;

	/**
	 * @param string $setting Class must implement OCA\Activity\ISetting
	 * @since 11.0.0
	 */
	public function registerSetting(string $setting): void;

	/**
	 * @return ActivitySettings[]
	 * @since 11.0.0
	 */
	public function getSettings(): array;

	/**
	 * @param string $provider Class must implement OCA\Activity\IProvider
	 * @since 11.0.0
	 */
	public function registerProvider(string $provider): void;

	/**
	 * @return IProvider[]
	 * @since 11.0.0
	 */
	public function getProviders(): array;

	/**
	 * @param string $id
	 * @return ActivitySettings
	 * @throws SettingNotFoundException when the setting was not found
	 * @since 11.0.0
	 * @since 30.0.0 throws {@see SettingNotFoundException} instead of \InvalidArgumentException
	 */
	public function getSettingById(string $id): ActivitySettings;

	/**
	 * @param string $type
	 * @param int $id
	 * @since 8.2.0
	 */
	public function setFormattingObject(string $type, int $id): void;

	/**
	 * @return bool
	 * @since 8.2.0
	 */
	public function isFormattingFilteredObject(): bool;

	/**
	 * @param bool $status Set to true, when parsing events should not use SVG icons
	 * @since 12.0.1
	 */
	public function setRequirePNG(bool $status): void;

	/**
	 * @return bool
	 * @since 12.0.1
	 */
	public function getRequirePNG(): bool;

	/**
	 * Set the user we need to use
	 *
	 * @param string|null $currentUserId
	 * @since 9.0.1
	 */
	public function setCurrentUserId(?string $currentUserId = null): void;

	/**
	 * Get the user we need to use
	 *
	 * Either the user is logged in, or we try to get it from the token
	 *
	 * @return string
	 * @throws \UnexpectedValueException If the token is invalid, does not exist or is not unique
	 * @since 8.1.0
	 */
	public function getCurrentUserId(): string;
}

<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Activity;

use OCP\Activity\Exceptions\UnknownActivityException;

/**
 * Interface IProvider
 *
 * @since 11.0.0
 */
interface IProvider {
	/**
	 * @param string $language The language which should be used for translating, e.g. "en"
	 * @param IEvent $event The current event which should be parsed
	 * @param IEvent|null $previousEvent A potential previous event which you can combine with the current one.
	 *                                   To do so, simply use setChildEvent($previousEvent) after setting the
	 *                                   combined subject on the current event.
	 * @return IEvent
	 * @throws UnknownActivityException Should be thrown if your provider does not know this event
	 * @since 11.0.0
	 * @since 30.0.0 Providers should throw {@see UnknownActivityException} instead of \InvalidArgumentException
	 *   when they did not handle the event. Throwing \InvalidArgumentException directly is deprecated and will
	 *   be logged as an error in Nextcloud 39.
	 */
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null);
}

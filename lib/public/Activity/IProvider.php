<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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

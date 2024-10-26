<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Activity;

/**
 * Interface IConsumer
 *
 * @since 6.0.0
 */
interface IConsumer {
	/**
	 * @param IEvent $event
	 * @return null
	 * @since 6.0.0
	 * @since 8.2.0 Replaced the parameters with an IEvent object
	 */
	public function receive(IEvent $event);
}

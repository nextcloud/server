<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class IContainer
 *
 * IContainer is the basic interface to be used for any internal dependency injection mechanism
 *
 * @since 6.0.0
 */
interface IContainer extends ContainerInterface {
	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @template T
	 * @param class-string<T>|string $id Identifier of the entry to look for.
	 *
	 * @throws NotFoundExceptionInterface No entry was found for **this** identifier.
	 * @throws ContainerExceptionInterface Error while retrieving the entry.
	 *
	 * @return ($id is class-string<T> ? T : mixed) Entry.
	 * @since 34.0.0
	 */
	#[\Override]
	public function get(string $id);
}

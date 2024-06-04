<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\TaskProcessing;

/**
 * This is the interface that is implemented by apps that
 * implement a task processing provider
 * @since 30.0.0
 */
interface IProvider {
	/**
	 * The unique id of this provider
	 * @since 30.0.0
	 */
	public function getId(): string;

	/**
	 * The localized name of this provider
	 * @since 30.0.0
	 */
	public function getName(): string;

	/**
	 * Returns the task type id of the task type, that this
	 * provider handles
	 *
	 * @since 30.0.0
	 * @return string
	 */
	public function getTaskTypeId(): string;

	/**
	 * @return int The expected average runtime of a task in seconds
	 * @since 30.0.0
	 */
	public function getExpectedRuntime(): int;

	/**
	 * Returns the shape of optional input parameters
	 *
	 * @since 30.0.0
	 * @psalm-return ShapeDescriptor[]
	 */
	public function getOptionalInputShape(): array;

	/**
	 * Returns the shape of optional output parameters
	 *
	 * @since 30.0.0
	 * @psalm-return ShapeDescriptor[]
	 */
	public function getOptionalOutputShape(): array;
}
